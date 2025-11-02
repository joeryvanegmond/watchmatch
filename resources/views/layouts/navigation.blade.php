<div class="row text-white">
    <div class="mb-4 d-flex flex-row justify-content-around">
        {{-- <span class="text-center small" style="font-size: 0.75rem;">{{number_format($brandCount)}} brands</span> --}}
        <span class="text-center small" style="font-size: 0.75rem;">{{number_format($watchCount)}} watches</span>
        <span class="text-center small" style="font-size: 0.75rem;">{{number_format($imageCount)}} images</span>
        <span class="text-center small" style="font-size: 0.75rem;">{{number_format($similarityCount)}} similarities</span>
    </div>
    <div class="d-flex justify-center">
        <div>
            <h4 class="logo text-center cursor-pointer" onclick="window.location.href = '/'">WatchMatch</h4>
        </div>
    </div>
    <div class="row">
        <navigation class="mb-4"></navigation>
    </div>
</div>