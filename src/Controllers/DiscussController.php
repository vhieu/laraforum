<?php

namespace Exp\Discuss\Controllers;

use Exp\Discuss\Models\Channel;
use Exp\Discuss\Requests\StoreDiscussRequest;
use Exp\Discuss\Models\Post;
use Exp\Discuss\Models\Thread;
use Exp\Discuss\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiscussController extends Controller
{
    protected $redirect_on_fail = 'discuss.index';


    public function show($channel, $thread)
    {
        $maxPostInPage = 15;
        $user = Auth::user();
        if ($user) {
            $user = User::with('profile')->find($user->id);
        }
        $thread = Thread::where('slug', $thread)->orWhere('id', $thread)
            ->with(['channel', 'user', 'answer.likes', 'answer.user.profile'])
            ->first();
        if ($thread->count() < 1) {
            return redirect()->route($this->redirect_on_fail);
        }
        if (strtolower($thread->channel->name) != strtolower($channel))
            return redirect()->route($this->redirect_on_fail);

        $posts = $thread->posts()->with(['user.profile'])->withCount('likes')->paginate($maxPostInPage);
        $reach_name = [];
        if ($reaches = ($user) ? $thread->anyReachesBy($user->id)->get() : [])
            foreach ($reaches as $reach) {
                $reach_name[] = $reach->name;
            }
        $thread->is_notified = (in_array('notify', $reach_name)) ? true : false;
        $thread->is_favourited = (in_array('favourite', $reach_name)) ? true : false;
        $thread->is_disliked = (in_array('dislike', $reach_name)) ? true : false;
        $user_liked_in_thread = ($user) ? $user->likes()->where('thread_id', $thread->id)->get()->pluck('id')->toArray() : [];
        if ($thread->answer)
            $thread->answer->is_liked = (in_array($thread->answer->id, $user_liked_in_thread)) ? true : false;
        foreach ($posts as $post) {
            $post->is_liked = (in_array($post->id, $user_liked_in_thread)) ? true : false;
        }
        $channels = Channel::all();
        return view('forum::discuss.show')->with(['thread' => $thread, 'posts' => $posts, 'user' => $user, 'channels' => $channels]);
    }

    /**
     *  function find thread by some option in view
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user = User::with('profile')->find($user->id);
        }
        $channels = Channel::all();
        $message = null;
        $search = null;
        $maxInPage = 20;
        switch ($filler = $this->getOneFillerName($request)) {
            case 'trending': {
                $order = DB::table('posts')->select(DB::raw('thread_id,count(id) AS total_posts'))
                    ->whereBetween('created_at', [new Carbon('last monday'), new Carbon('now')])
                    ->groupBy('thread_id')
                    ->orderBy('total_posts', 'DESC');
                $order = $order->paginate($maxInPage);
                $order->appends($request->all());
                $threads = Thread::whereIn('id', $order->pluck('thread_id'))
                    ->with(['channel', 'user', 'last_post.user', 'user.profile'])
                    ->get();
                foreach ($threads as $thread) {
                    $thread->posts_count = $order->where('thread_id', $thread->id)->first()->total_posts;
                }
                $threads = $threads->sortByDesc('posts_count');
                $threads->links = ($order->total() != $order->count()) ? $order->links() : '';
                return view('forum::discuss.index')
                    ->with(['threads' => $threads, 'user' => $user, 'message' => $message, 'search' => $search, 'channels' => $channels]);
                break;
            }
            case 'popular': {
                $order = DB::table('posts')->select(DB::raw('thread_id,count(id) AS total_posts'))
                    ->groupBy('thread_id')->orderBy('total_posts', 'DESC');
                $order = $order->paginate($maxInPage);
                $order->appends($request->all());
                $threads = Thread::whereIn('id', $order->pluck('thread_id'))
                    ->with(['channel', 'user', 'last_post.user', 'user.profile'])
                    ->get();
                foreach ($threads as $thread) {
                    $thread->posts_count = $order->where('thread_id', $thread->id)->first()->total_posts;
                }
                $threads = $threads->sortByDesc('posts_count');
                $threads->links = ($order->total() != $order->count()) ? $order->links() : '';
                return view('forum::discuss.index')
                    ->with(['threads' => $threads, 'user' => $user, 'message' => $message, 'search' => $search, 'channels' => $channels]);
                break;
            }
            case 'answered': {
                $threads = Thread::answered($request->input('answered'))->orderByNomal();
                break;
            }
            case 'me': {
                $threads = $user->threads();
                break;
            }
            case 'favourites': {
                $threads = $user->favourites();
                break;
            }
            case 'contributed_to': {
                $threads = Thread::contributesBy($user->id);
                break;
            }
            case 'search': {
                $search = $request->input('search');
                $search = trim($search, ' ');
                $search = preg_replace('/[\s]+/u', ' ', $search);
                $search_key = '%' . $search . '%';
                $threads = Thread::withContain($search_key)->orderByNomal();
                break;
            }
            case 'dynamic_search': {
                $search = $request->input('dynamic_search');
                $search_key = ' ' . $search . ' ';
                $search_key = preg_replace('/[\W\s]+/u', '%', $search_key);
                $threads = Thread::withContain($search_key)->orderByNomal();
                $message = 'We can\'t find discuss exists your key, this is result of dynamic search.';
                break;
            }
            default: {
                $threads = Thread::orderByNomal();
                break;
            }
        }
        $threads = $threads->with(['channel', 'user', 'last_post.user', 'user.profile']);
        if ($request->has('channel'))
            $threads->inChannel($request->input('channel'));
        $threads = $threads->paginate($maxInPage);
        $threads->appends($request->all());
        if ($filler == 'search' && $threads->total() < 1)
            return redirect()->route('discuss.index', ['dynamic_search' => $request->input('search')]);
        if ($filler == 'dynamic_search' && $threads->total() < 1)
            $message = 'We can\'t find anything with this key, please try another';
        if ($filler == 'search' || $filler == 'dynamic_search')
            $threads = $this->highlightKey($threads, $search_key);
        if ($search)
            $search = preg_replace('/[\W\s]+/u', ' ', $search);
        //add post_count
        $data_posts_count = DB::table('posts')->select(DB::raw('thread_id,count(id) as total_posts'))
            ->whereIn('thread_id', $threads->pluck('id'))
            ->groupBy('thread_id')
            ->get();
        foreach ($threads as $thread) {
            $thread->posts_count = ($value = $data_posts_count->where('thread_id', $thread->id)->first()) ? $value->total_posts : 0;
        }
        // add links paginate
        $threads->links = ($threads->total() != $threads->count()) ? $threads->links() : '';
        return view('forum::discuss.index')
            ->with(['threads' => $threads, 'user' => $user, 'message' => $message, 'search' => $search, 'channels' => $channels]);
    }

    protected function highlightKey($threads, $search_key, $color = 'rgba(255, 191, 30, 0.44)')
    {
        $key = trim($search_key, '%');
        $keys = explode('%', $key);
        $keys = array_unique($keys);
        foreach ($threads as $thread) {
            $thread->title_with_highlight = $this->highlightStr($keys, $thread->title, $color);
            $thread->body_with_highlight = $this->highlightStr($keys, $thread->body, $color);
        }
        return $threads;
    }

    protected function highlightStr($source_keys, $str, $color)
    {
        $start_highlight = "<span style='background-color:$color'>";
        $end_highlight = "</span>";
        $keys = [];
        foreach ($source_keys as $source_key) {
            $keys[] = $start_highlight . $source_key . $end_highlight;
        }
        return str_ireplace($source_keys, $keys, $str);
    }

    private function getOneFillerName($request)
    {
        if (Auth::check()) {
            if ($request->has('me'))
                return 'me';
            if ($request->has('contributed_to'))
                return 'contributed_to';
            if ($request->has('favourites'))
                return 'favourites';
        }
        if ($request->has('trending'))
            return 'trending';
        if ($request->has('popular'))
            return 'popular';
        if ($request->has('answered'))
            return 'answered';
        if ($request->has('search'))
            return 'search';
        if ($request->has('dynamic_search'))
            return 'dynamic_search';
        return 'nothing';
    }

    /**
     * @param StoreDiscuss $request
     */
    public function store(StoreDiscussRequest $request)
    {
        $user = Auth::user();
        if ($time = $this->isIntervalLimit($user))
            return redirect()->back()
                ->withErrors(['msg' => "You need wait more " . $time . " minutes to create new conversation"]);
        $data = $request->all();
        $thread = Thread::where('title', $data['title'])->first();
        if ($thread)
            return redirect()->back()->withErrors(['title.unique' => 'This discuss is exist you can find it in search bar']);
        $thread = new Thread;
        $thread->user_id = $user->id;
        $thread->channel_id = $data['channel'];
        $thread->title = $data['title'];
        $thread->body = $data['body'];
        $thread->save();
        return redirect()->route('discuss.show', [$thread->channel->name, $thread->slug]);
    }

    protected function isIntervalLimit($user)
    {
        $limit_time = 5;//minutes
        if (!($last_created = $user->threads()->latest()->first()))
            return 0;
        if ($time = Carbon::now()->diffInMinutes($last_created->created_at) > $limit_time)
            return 0;
        return $limit_time - $time;
    }

    public function edit($thread_id)
    {
        $user = Auth::user();
        if ($user) {
            $user = User::with('profile')->find($user->id);
        }
        if (!$user)
            return redirect()->route($this->redirect_on_fail);
        $thread = Thread::with(['user', 'channel'])->find($thread_id);
        if (!$thread)
            return redirect()->route($this->redirect_on_fail);
        if ($thread->user->id != $user->id)
            return redirect()->route($this->redirect_on_fail);
        $channels_data = Channel::orderBy('name')->get();
        $channels = [];
        $channels[0] = 'Pick a Channel...';
        foreach ($channels_data as $channel) {
            $channels[$channel->id] = title_case($channel->name);
        }
        return view('forum::discuss.edit')->with(['channels' => $channels, 'user' => $user, 'thread' => $thread]);
    }

    public function update(StoreDiscussRequest $request, $thread_id)
    {
        $user = Auth::user();
        if (!$user)
            return redirect()->route($this->redirect_on_fail);
        $thread = Thread::with(['user', 'channel'])->find($thread_id);
        if (!$thread)
            return redirect()->route($this->redirect_on_fail);
        if ($thread->user->id != $user->id)
            return redirect()->route($this->redirect_on_fail);
        $data = $request->all();
        if ($data['title'] != $thread->title) {
            $thread_check = Thread::where('title', $data['title'])->first();
            if ($thread_check)
                return redirect()->back()->withErrors(['title.unique' => 'This discuss is exist you can find it in search bar']);
        }
        $thread->channel_id = $data['channel'];
        $thread->slug = null;//trigger to update slug
        $thread->title = $data['title'];
        $thread->body = $data['body'];
        $thread->author_updated_at = Carbon::now();
        $thread->save();
        return redirect()->route('discuss.show', [$thread->channel->name, $thread->slug]);
    }

    public function create()
    {
        $user = Auth::user();
        if ($user) {
            $user = User::with('profile')->find($user->id);
        }
        if (!$user)
            return redirect()->route($this->redirect_on_fail);
        $channels_data = Channel::orderBy('name')->get();
        $channels = [];
        $channels[0] = 'Pick a Channel...';
        foreach ($channels_data as $channel) {
            $channels[$channel->id] = title_case($channel->name);
        }
        return view('forum::discuss.create')->with(['channels' => $channels, 'user' => $user]);
    }

    public function setBestAnswer($thread_id, $post_id)
    {
        $user = Auth::user();
        $thread = Thread::with('user')->find($thread_id);
        if (!$thread || $thread->user->id != $user->id)
            return redirect()->route($this->redirect_on_fail);
        $post = Post::with('thread')->find($post_id);
        if (!$post || $thread->id != $post->thread->id)
            return redirect()->route($this->redirect_on_fail);
        DB::table('threads')->where('id', $thread_id)->update(['best_answer_id' => $post->id]);
        return redirect()->back();
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required'
        ]);
        if ($validator->fails())
            return redirect()->route($this->redirect_on_fail);
    }
}
