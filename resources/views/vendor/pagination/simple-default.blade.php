@if ($paginator->hasPages())
    <ul class="pagination pagination-rounded justify-content-end mb-2">
        <li class="page-item disabled">
            <a class="page-link" href="javascript: void(0);" aria-label="Previous">
                <i class="mdi mdi-chevron-left"></i>
            </a>
        </li>
        <li class="page-item active"><a class="page-link" href="javascript: void(0);">1</a></li>
        <li class="page-item"><a class="page-link" href="javascript: void(0);">2</a></li>
        <li class="page-item"><a class="page-link" href="javascript: void(0);">3</a></li>
        <li class="page-item"><a class="page-link" href="javascript: void(0);">4</a></li>
        <li class="page-item"><a class="page-link" href="javascript: void(0);">5</a></li>
        <li class="page-item">
            <a class="page-link" href="javascript: void(0);" aria-label="Next">
                <i class="mdi mdi-chevron-right"></i>
            </a>
        </li>
    </ul>

@endif
