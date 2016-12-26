<?php

namespace Exp\Discuss\Controllers;

use Exp\Discuss\Models\Post;
use Exp\Discuss\Models\Thread;
use Exp\Discuss\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    protected $redirect_on_fail = 'discuss.index';

    public function store(Request $request, $thread_id)
    {
        $user = Auth::user();
        if($user)
        {
            $user=User::with('profile')->find($user->id);
        }
        if (!$user)
            return redirect()->route($this->redirect_on_fail);
        if (!Thread::where('id', $thread_id))
            return redirect()->route($this->redirect_on_fail);
        if ($time = $this->isIntervalLimit())
            return redirect()->back()->withErrors(['msg' => "Please relax !"]);
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:60000'
        ]);
        if ($validator->fails())
            return redirect()->back()->withErrors(['msg' => "Please don't store a book in there!"]);
        $body = $request->input('body');
        $post = new Post;
        $post->user_id = $user->id;
        $post->thread_id = $thread_id;
        $post->body = $body;
        $post->save();
        return redirect()->back();
    }

    /**
     * @return check is user spam.
     */
    protected function isIntervalLimit()
    {
        $limit_time = 20;//second
        if (!($last_created = Auth::user()->with('profile')->first()->posts()->latest()->first()))
            return 0;
        if ($time = Carbon::now()->diffInSeconds($last_created->created_at) > $limit_time)
            return 0;
        return $limit_time - $time;
    }
    public function edit($post_id)
    {
        $user = Auth::user();
        if($user)
        {
            $user=User::with('profile')->find($user->id);
        }
        if(!$user)
            return redirect()->back();
        $post=Post::where('id',$post_id)->with('user')->first();
        if($post && $post->user->id == $user->id)
            return redirect()->back()->withInput(['post_editing_id'=>$post->id]);
        return redirect()->back();
    }
    public function update(Request $request)
    {
        $user = Auth::user();
        if($user)
        {
            $user=User::with('profile')->find($user->id);
        }
        if (!$user)
            return redirect()->route($this->redirect_on_fail);
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'body' => 'required|max:60000'
        ]);
        if ($validator->fails())
            return redirect()->route($this->redirect_on_fail);
        $post = Post::where('id', $request->input('id'))->with('user')->first();
        if ($post->user->id != $user->id)
            return redirect()->route($this->redirect_on_fail);
        $post->body = $request->input('body');
        $post->save();
        return redirect()->back();
    }

    public function destroy($post_id)
    {
        $user = Auth::user();
        if($user)
        {
            $user=User::with('profile')->find($user->id);
        }
        if (!$user)
            return redirect()->route($this->redirect_on_fail);
        $post = Post::where('id', $post_id)->with('user')->first();
        if ($post->user->id != $user->id)
            return redirect()->route($this->redirect_on_fail);
        $post->delete();
        return redirect()->back();
    }


}
