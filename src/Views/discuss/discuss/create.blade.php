@extends('forum::'.config('laraforum.template').'.layouts.nomal')
@section('primary')

    <div class="box">
    {{Form::open(['url'=>route('discuss.store'),
                    'autocomplete'=>'off'])}}
        <div class="control">
            <label for="channel" class="label">Pick a Channel:</label>
            <span class="select">
        {{Form::select('channel',$channels,0)}}
    </span>


            @if($errors->has('channel'))
                @foreach($errors->get('channel') as $message)
                    <span class="help is-danger">{{$message}}</span>
                @endforeach
            @endif

        </div>

        <div class="control">
            <label for="title" class="label">Provide a Title:</label>
            {{Form::text('title',null,[
                'class'=>'input',
                'required'
            ])}}
            @if($errors->has('title'))
                @foreach($errors->get('title') as $message)
                    <span class="help is-danger">{{$message}}</span>
                @endforeach
            @endif
        </div>

        <div class="control">
            <label for="body" class="label">Ask Away:</label>

            <textarea id="body" class="textarea " name="body" data-autosize="" required="" placeholder="What do you need help with? Be specific, so that your peers are better able to assist you."
                      style="overflow: hidden; word-wrap: break-word; resize: none; height: 147.6px;"></textarea>
            @if($errors->has('body'))
                @foreach($errors->get('body') as $message)
                    <span class="help is-danger">{{$message}}</span>
                @endforeach
            @endif
            @if($errors->has('msg'))
                @foreach($errors->get('msg') as $message)
                    <span class="help is-danger">{{$message}}</span>
                @endforeach
            @endif
        </div>

        {{-- <div class="control">
             <div class="g-recaptcha mar-b" data-sitekey="6Le3mxUTAAAAAIWZK2nsVwKTOZY7J4sM14WSlYAy"><div style="width: 304px; height: 78px;"><div><iframe src="https://www.google.com/recaptcha/api2/anchor?k=6Le3mxUTAAAAAIWZK2nsVwKTOZY7J4sM14WSlYAy&amp;co=aHR0cHM6Ly9sYXJhY2FzdHMuY29tOjQ0Mw..&amp;hl=vi&amp;v=r20161206104336&amp;size=normal&amp;cb=kxx9pm266wip" title="tiện ích con mã xác thực lại" width="304" height="78" frameborder="0" scrolling="no" name="undefined"></iframe></div><textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid #c1c1c1; margin: 10px 25px; padding: 0px; resize: none;  display: none; "></textarea></div></div>
         </div>--}}
        <div class="control is-flex">
            {{--                <p class="help flex is-hidden-mobile">
                                * You may use Markdown with
                                <a href="https://help.github.com/articles/github-flavored-markdown" target="_blank" rel="noreferrer noopener">GitHub-flavored</a>
                                code blocks.
                            </p>--}}
            <div class="control is-grouped w-100-mobile is-aligned-center-mobile">
                <div class="control mb-1-mobile">
                    <a href="{{route('discuss.index')}}" class="button is-muted is-default">
                        Cancel
                    </a>
                </div>
                <div class="control">
                    {{Form::button('Publish Discussion',['class'=>'button is-primary is-outlined','type'=>'submit'])}}
                    {{--<button type="submit" class="button is-primary is-outlined--}}{{-- is-disabled--}}{{--"
                            data-single-click="">
                        Publish Discussion
                    </button>--}}
                </div>
            </div>
        </div>
        <!-- Form Errors -->
        {{Form::close()}}
    </div>


@stop