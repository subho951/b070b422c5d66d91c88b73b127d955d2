@props(['compact' => false])

<div @class(['location-map', 'location-map-compact' => $compact])>
    @unless($compact)
        <div class="location-map-heading">
            <span class="eyebrow">FIND US</span>
            <h2>Howvanta Technologies</h2>
            <p>{{ $siteSettings['contact_location'] ?? 'Howrah, West Bengal, India' }}</p>
        </div>
    @endunless
    <iframe
        src="https://www.google.com/maps?cid=2162906646691477460&amp;output=embed"
        title="Howvanta Technologies location on Google Maps"
        width="600"
        height="{{ $compact ? 200 : 400 }}"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen
    ></iframe>
    <a class="location-map-link" href="https://maps.app.goo.gl/FrwtE6Fu5k86bNHp6" target="_blank" rel="noopener noreferrer">Open in Google Maps <span aria-hidden="true">&nearr;</span></a>
</div>
