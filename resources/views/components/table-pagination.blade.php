<div class="mt-5 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
    <p class="text-sm text-gray-600 dark:text-gray-300">
        Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} records
    </p>
    @if($paginator->hasPages())
        <div>{{ $paginator->onEachSide(1)->links() }}</div>
    @else
        <span class="text-sm text-gray-500 dark:text-gray-400">Page 1 of 1</span>
    @endif
</div>
