@if ($paginator->hasPages())
    <style>
        .custom-pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            margin-bottom: 2rem;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 999px;
            /* Pill shape */
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .custom-pagination-container .pagination-link,
        .custom-pagination-container .pagination-number {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            min-width: 40px;
            padding: 0 1rem;
            border-radius: 999px;
            color: #94a3b8;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
            font-family: inherit;
        }

        /* Hover state */
        .custom-pagination-container .pagination-link:hover:not(.disabled),
        .custom-pagination-container .pagination-number:hover {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }

        /* Active Page */
        .custom-pagination-container .pagination-number.active {
            background: #6366f1;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        /* Disabled state */
        .custom-pagination-container .pagination-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            color: #64748b;
        }

        .custom-pagination-container .pagination-numbers {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .custom-pagination-container i {
            font-size: 1.2rem;
        }
    </style>

    <nav class="custom-pagination-container" role="navigation" aria-label="Pagination Navigation">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-link disabled" aria-disabled="true">
                <i class="ri-arrow-left-s-line"></i>
                <span style="margin-left:0.5rem">Previous</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-link">
                <i class="ri-arrow-left-s-line"></i>
                <span style="margin-left:0.5rem">Previous</span>
            </a>
        @endif

        {{-- Pagination Elements --}}
        <div class="pagination-numbers">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-dots" style="color:#64748b; padding:0 0.5rem;">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-number active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-number">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-link">
                <span style="margin-right:0.5rem">Next</span>
                <i class="ri-arrow-right-s-line"></i>
            </a>
        @else
            <span class="pagination-link disabled" aria-disabled="true">
                <span style="margin-right:0.5rem">Next</span>
                <i class="ri-arrow-right-s-line"></i>
            </span>
        @endif
    </nav>
@endif