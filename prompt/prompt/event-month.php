<style>
    .month-animate {
        opacity: 0;
        transition: opacity 0.55s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .month-animate[data-dir="left"] {
        transform: translateX(-40px);
    }

    .month-animate[data-dir="right"] {
        transform: translateX(40px);
    }

    .month-animate.visible {
        opacity: 1;
        transform: translateX(0);
    }
</style>


{{-- ======= AUGUST ======= --}}
<section class="relative z-10 mb-stack-lg pl-0 lg:pl-24 month-animate" data-dir="left">
    <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
    <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
        August</h2>
    <div class="grid grid-cols-1 gap-gutter">
        <div class="event-card-border bg-white rounded-DEFAULT relative overflow-hidden group blue-glow shadow-sm">
            {{-- COLLAGE: 2-panel split --}}
            <div class="grid gap-1 overflow-hidden rounded-t-DEFAULT"
                style="grid-template-columns:3fr 2fr;height:130px">
                <img src="/images/aug-main.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/aug-side.jpg" class="w-full h-full object-cover" alt="">
            </div>
            <div class="p-gutter">
                <div class="flex justify-between items-start mb-base">
                    <h3 class="font-headline-md text-headline-md text-black font-bold">Harvest Market</h3>
                    <span class="font-label-caps text-label-caps text-secondary font-bold">AUG 3</span>
                </div>
                <p class="font-body-md text-body-md text-black mb-stack-sm">Farm-to-table vendors, local produce, and
                    artisan preserves.</p>
                <button
                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                    Details</button>
            </div>
        </div>
    </div>
</section>

{{-- ======= SEPTEMBER ======= --}}
<section class="relative z-10 mb-stack-lg pl-0 lg:pl-24 month-animate" data-dir="right">
    <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
    <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
        September</h2>
    <div class="grid grid-cols-1 gap-gutter">
        <div class="event-card-border bg-white rounded-DEFAULT relative overflow-hidden group blue-glow shadow-sm">
            {{-- COLLAGE: tall main + 2 stacked --}}
            <div class="grid gap-1 overflow-hidden rounded-t-DEFAULT"
                style="grid-template-columns:3fr 2fr;grid-template-rows:80px 80px;">
                <img src="/images/sep-main.jpg" class="w-full h-full object-cover" style="grid-row:span 2" alt="">
                <img src="/images/sep-top.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/sep-btm.jpg" class="w-full h-full object-cover" alt="">
            </div>
            <div class="p-gutter">
                <div class="flex justify-between items-start mb-base">
                    <h3 class="font-headline-md text-headline-md text-black font-bold">Art & Craft Fair</h3>
                    <span class="font-label-caps text-label-caps text-secondary font-bold">SEP 14</span>
                </div>
                <p class="font-body-md text-body-md text-black mb-stack-sm">Independent artists and makers showcase
                    handcrafted works.</p>
                <button
                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                    Details</button>
            </div>
        </div>
    </div>
</section>

{{-- ======= OCTOBER ======= --}}
<section class="relative z-10 mb-stack-lg pl-0 lg:pl-24 month-animate" data-dir="left">
    <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
    <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
        October</h2>
    <div class="grid grid-cols-1 gap-gutter">
        <div class="event-card-border bg-white rounded-DEFAULT relative overflow-hidden group blue-glow shadow-sm">
            {{-- COLLAGE: 3-column banner --}}
            <div class="grid gap-1 overflow-hidden rounded-t-DEFAULT"
                style="grid-template-columns:1fr 1fr 1fr;height:110px">
                <img src="/images/oct-1.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/oct-2.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/oct-3.jpg" class="w-full h-full object-cover" alt="">
            </div>
            <div class="p-gutter">
                <div class="flex justify-between items-start mb-base">
                    <h3 class="font-headline-md text-headline-md text-black font-bold">Lagos Sound Fest</h3>
                    <span class="font-label-caps text-label-caps text-secondary font-bold">OCT 19</span>
                </div>
                <p class="font-body-md text-body-md text-black mb-stack-sm">Three stages, twenty acts, one unforgettable
                    outdoor evening.</p>
                <button
                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                    Details</button>
            </div>
        </div>
    </div>
</section>

{{-- ======= NOVEMBER ======= --}}
<section class="relative z-10 mb-stack-lg pl-0 lg:pl-24 month-animate" data-dir="right">
    <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
    <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
        November</h2>
    <div class="grid grid-cols-1 gap-gutter">
        <div class="event-card-border bg-white rounded-DEFAULT relative overflow-hidden group blue-glow shadow-sm">
            {{-- COLLAGE: hero + 2 side tiles --}}
            <div class="grid gap-1 overflow-hidden rounded-t-DEFAULT"
                style="grid-template-columns:2fr 1fr;grid-template-rows:75px 75px;">
                <img src="/images/nov-main.jpg" class="w-full h-full object-cover" style="grid-row:span 2" alt="">
                <img src="/images/nov-top.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/nov-btm.jpg" class="w-full h-full object-cover" alt="">
            </div>
            <div class="p-gutter">
                <div class="flex justify-between items-start mb-base">
                    <h3 class="font-headline-md text-headline-md text-black font-bold">Coastal Regatta</h3>
                    <span class="font-label-caps text-label-caps text-secondary font-bold">NOV 2</span>
                </div>
                <p class="font-body-md text-body-md text-black mb-stack-sm">Sailing races and waterfront celebrations
                    along the Victoria Island shoreline.</p>
                <button
                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                    Details</button>
            </div>
        </div>
    </div>
</section>

{{-- ======= DECEMBER ======= --}}
<section class="relative z-10 mb-stack-lg pl-0 lg:pl-24 month-animate" data-dir="left">
    <div class="absolute left-[-5px] top-12 w-3 h-3 bg-secondary rounded-full hidden lg:block rotate-45"></div>
    <h2 class="font-ui-button text-ui-button text-secondary uppercase tracking-widest mb-stack-md text-xl font-bold">
        December</h2>
    <div class="grid grid-cols-1 gap-gutter">
        <div class="event-card-border bg-white rounded-DEFAULT relative overflow-hidden group blue-glow shadow-sm">
            {{-- COLLAGE: 2×2 equal mosaic --}}
            <div class="grid gap-1 overflow-hidden rounded-t-DEFAULT"
                style="grid-template-columns:1fr 1fr;grid-template-rows:75px 75px;">
                <img src="/images/dec-1.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/dec-2.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/dec-3.jpg" class="w-full h-full object-cover" alt="">
                <img src="/images/dec-4.jpg" class="w-full h-full object-cover" alt="">
            </div>
            <div class="p-gutter">
                <div class="flex justify-between items-start mb-base">
                    <h3 class="font-headline-md text-headline-md text-black font-bold">Yuletide Night Market</h3>
                    <span class="font-label-caps text-label-caps text-secondary font-bold">DEC 20</span>
                </div>
                <p class="font-body-md text-body-md text-black mb-stack-sm">Festive stalls, live carols, and warm drinks
                    under string lights.</p>
                <button
                    class="self-start font-ui-button text-ui-button text-secondary border border-secondary px-6 py-2 rounded-DEFAULT hover:bg-secondary hover:text-on-secondary transition-all">View
                    Details</button>
            </div>
        </div>
    </div>
</section>

<script>
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.month-animate').forEach(el => obs.observe(el));
</script>