<div
    @if($htmlId !== '') id="{{ $htmlId }}" @endif
    class="{{ $classes }} fpb-countdown"
    data-fpb-id="{{ $id }}"
    data-fpb-type="countdown"
    style="background: var(--fpb-bg, #0f172a); color: var(--fpb-color, #ffffff); border-radius: var(--fpb-radius, 12px); padding: 24px; text-align: center;"
    x-data="{
        target: new Date(@js($settings['target_iso'])).getTime(),
        days: 0, hours: 0, minutes: 0, seconds: 0,
        init() {
            const tick = () => {
                const diff = Math.max(0, this.target - Date.now());
                this.days = Math.floor(diff / 86400000);
                this.hours = Math.floor((diff % 86400000) / 3600000);
                this.minutes = Math.floor((diff % 3600000) / 60000);
                this.seconds = Math.floor((diff % 60000) / 1000);
            };
            tick();
            setInterval(tick, 1000);
        }
    }"
>
    <div style="display: flex; justify-content: center; gap: 16px; font-weight: 700;">
        <div><div style="font-size: 36px;" x-text="days">0</div><div style="font-size: 11px; opacity: 0.7;">DAYS</div></div>
        <div><div style="font-size: 36px;" x-text="String(hours).padStart(2, '0')">00</div><div style="font-size: 11px; opacity: 0.7;">HOURS</div></div>
        <div><div style="font-size: 36px;" x-text="String(minutes).padStart(2, '0')">00</div><div style="font-size: 11px; opacity: 0.7;">MIN</div></div>
        <div><div style="font-size: 36px;" x-text="String(seconds).padStart(2, '0')">00</div><div style="font-size: 11px; opacity: 0.7;">SEC</div></div>
    </div>
</div>
