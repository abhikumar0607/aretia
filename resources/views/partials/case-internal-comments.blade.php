<section class="case-panel-card card case-internal-comments">
    <div class="case-panel-head">
        <h3>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            Internal comments
        </h3>
        <span class="pill pill-muted">Team only</span>
    </div>

    @php $comments = $case->comments; @endphp

    @if($comments->count())
        <div class="case-comments-list">
            @foreach($comments as $comment)
                <article class="case-comment-item">
                    <div class="case-comment-head">
                        <strong class="case-comment-author">{{ $comment->authorLabel() }}</strong>
                        <time class="case-comment-time" datetime="{{ $comment->created_at->toIso8601String() }}">
                            {{ $comment->created_at->format('d M Y, H:i') }}
                        </time>
                    </div>
                    <p class="case-comment-body">{!! nl2br(e($comment->body)) !!}</p>
                </article>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('cases.comments.store', $case) }}" class="case-internal-comments-form">
        @csrf
        <div class="form-field">
            <label for="case_comment_body">Add a comment</label>
            <textarea name="body" id="case_comment_body" rows="3" placeholder="Share an internal note with the team…" required maxlength="5000">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Post comment</button>
    </form>
</section>
