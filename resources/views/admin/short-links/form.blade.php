{{-- Shared short link fields. Expects $shortLink and $couponOptions. --}}
<x-admin.card title="Link">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="label" label="Name" :value="$shortLink->label" required maxlength="100" placeholder="Instagram bio, Eid video, Influencer Sara" hint="For the team. Shoppers don't see it." />
        <x-admin.input
            name="code"
            label="Short code (optional)"
            :value="$shortLink->code"
            maxlength="20"
            :prefix="str(url('p'))->after('://').'/'"
            placeholder="random"
            :hint="$shortLink->exists ? 'Changing it stops links already shared from working.' : 'Leave it empty for a random code, or choose one such as eid-sara.'"
            class="lowercase"
        />
        <div class="sm:col-span-2">
            <x-admin.input
                name="destination"
                label="Page it opens"
                :value="$shortLink->destination"
                required
                maxlength="500"
                placeholder="/ar/products/abaya?color=navy"
                hint="Open the page in the store, choose the language and color, then copy the address here. Use /ar/… or /en/… to pick the language."
            />
        </div>
    </div>
</x-admin.card>

<x-admin.card title="Campaign tags" description="They show where orders came from, here and on each order. Leave a field empty to skip it.">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.input name="utm_source" label="Source" :value="$shortLink->utm_source" maxlength="100" placeholder="instagram" list="utm-sources" hint="Where the link is posted." />
        <x-admin.input name="utm_medium" label="Medium" :value="$shortLink->utm_medium" maxlength="100" placeholder="social" list="utm-mediums" hint="How: social post, bio, paid ad, influencer." />
        <x-admin.input name="utm_campaign" label="Campaign" :value="$shortLink->utm_campaign" maxlength="100" placeholder="eid-2026" />
        <x-admin.input name="utm_content" label="Content" :value="$shortLink->utm_content" maxlength="100" placeholder="navy-video-1" hint="Tells apart several posts in one campaign." />
    </div>

    <datalist id="utm-sources">
        @foreach (['instagram', 'tiktok', 'snapchat', 'facebook', 'whatsapp', 'google'] as $source)
            <option value="{{ $source }}"></option>
        @endforeach
    </datalist>
    <datalist id="utm-mediums">
        @foreach (['social', 'bio', 'story', 'paid', 'influencer', 'message'] as $medium)
            <option value="{{ $medium }}"></option>
        @endforeach
    </datalist>
</x-admin.card>

<x-admin.card title="Options">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-admin.select name="coupon_code" label="Apply a discount code" :options="$couponOptions" :value="$shortLink->coupon_code" hint="Shoppers get it in their bag when they open the link." />
        <x-admin.toggle name="is_active" label="Link works" :checked="$shortLink->exists ? $shortLink->is_active : true" hint="When off, the link shows “page not found”." />
    </div>
</x-admin.card>
