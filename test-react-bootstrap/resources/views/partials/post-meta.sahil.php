{{-- Partial: `post` is passed explicitly by the including template. --}}
<p class="meta">
    @if (post.author)
        by {{ post.author }}
    @else
        by unknown
    @endif

    @if (post.published)
        · published
    @else
        · draft
    @endif
</p>
