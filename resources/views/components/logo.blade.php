{{-- Logo ForeCash (salinan public/logo.svg versi Next.js). Contoh: <x-logo width="40" height="40" /> --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" role="img" aria-label="Logo ForeCash" {{ $attributes }}>
<defs>
  <radialGradient id="Lface" cx="0.36" cy="0.30" r="0.80">
    <stop offset="0" stop-color="#FFF4B8"/><stop offset="0.30" stop-color="#FFDB5C"/>
    <stop offset="0.72" stop-color="#F6B521"/><stop offset="1" stop-color="#E29A0E"/>
  </radialGradient>
  <linearGradient id="Lrim" x1="0.2" y1="0" x2="0.8" y2="1">
    <stop offset="0" stop-color="#FFE68A"/><stop offset="0.45" stop-color="#F3AE1C"/><stop offset="1" stop-color="#C98208"/>
  </linearGradient>
  <linearGradient id="LrimIn" x1="0.8" y1="1" x2="0.2" y2="0">
    <stop offset="0" stop-color="#FFE68A"/><stop offset="0.5" stop-color="#F3AE1C"/><stop offset="1" stop-color="#C98208"/>
  </linearGradient>
  <linearGradient id="Ledge" x1="0" y1="0" x2="1" y2="0">
    <stop offset="0" stop-color="#D99112"/><stop offset="0.5" stop-color="#B9750A"/><stop offset="1" stop-color="#8E5605"/>
  </linearGradient>
  <radialGradient id="Leye" cx="0.4" cy="0.35" r="0.7">
    <stop offset="0" stop-color="#5A3A1E"/><stop offset="1" stop-color="#2A1A0C"/>
  </radialGradient>
  <linearGradient id="Larm" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="#FFD24A"/><stop offset="1" stop-color="#E29A0E"/>
  </linearGradient>
  <clipPath id="LedgeClip"><path d="M128 40 a86 86 0 0 1 0 172 h10 a86 86 0 0 0 0 -172 z"/></clipPath>
</defs>
<defs>
  <linearGradient id="tile" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFA23F"/><stop offset="1" stop-color="#FF7A12"/></linearGradient>
  <linearGradient id="tileEdge" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#E86A08"/><stop offset="1" stop-color="#C45604"/></linearGradient>
</defs>
<rect x="8" y="18" width="240" height="232" rx="58" fill="url(#tileEdge)"/>
<rect x="8" y="6" width="240" height="232" rx="58" fill="url(#tile)"/>
<path d="M44 30 Q128 8 212 30" stroke="#FFD9A8" stroke-width="9" stroke-linecap="round" fill="none" opacity=".7"/>
<g transform="translate(128 124) scale(0.86) translate(-128 -126)"><g transform="translate(0 0)">
  <ellipse cx="134" cy="226" rx="70" ry="10" fill="#7A4704" opacity=".22"/>
  <path d="M128 40 a86 86 0 0 1 0 172 h10 a86 86 0 0 0 0 -172 z" fill="url(#Ledge)"/>
  <g clip-path="url(#LedgeClip)"><rect x="120" y="40" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="46" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="52" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="58" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="64" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="70" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="76" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="82" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="88" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="94" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="100" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="106" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="112" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="118" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="124" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="130" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="136" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="142" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="148" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="154" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="160" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="166" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="172" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="178" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="184" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="190" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="196" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="202" width="40" height="2.6" fill="#6E3F03" opacity=".38"/><rect x="120" y="208" width="40" height="2.6" fill="#6E3F03" opacity=".38"/></g>
  <circle cx="128" cy="126" r="86" fill="url(#Lrim)"/>
  <circle cx="128" cy="126" r="72" fill="url(#LrimIn)"/>
  <circle cx="128" cy="126" r="68" fill="url(#Lface)"/>
  <circle cx="128" cy="126" r="58" fill="none" stroke="#E6A117" stroke-width="3" opacity=".75"/>
  <circle cx="128" cy="127.5" r="58" fill="none" stroke="#FFF2B0" stroke-width="1.6" opacity=".7"/>
  <path d="M60 104 A70 70 0 0 1 112 52" stroke="#FFFBE6" stroke-width="9" stroke-linecap="round" fill="none" opacity=".75"/>
  <path d="M70 128 A60 60 0 0 1 76 104" stroke="#FFFBE6" stroke-width="5" stroke-linecap="round" fill="none" opacity=".5"/>
  <path d="M186 168 A70 70 0 0 1 150 196" stroke="#B86F06" stroke-width="6" stroke-linecap="round" fill="none" opacity=".35"/>
  <text x="128" y="86" text-anchor="middle" font-family="Arial Rounded MT Bold, Arial, sans-serif" font-weight="800" font-size="17" fill="#D18A0C" opacity=".85">Rp</text>
  <text x="128" y="85" text-anchor="middle" font-family="Arial Rounded MT Bold, Arial, sans-serif" font-weight="800" font-size="17" fill="#FFF2B0" opacity=".55">Rp</text>
  <ellipse cx="104" cy="122" rx="11" ry="14" fill="url(#Leye)"/>
  <ellipse cx="152" cy="122" rx="11" ry="14" fill="url(#Leye)"/>
  <circle cx="108" cy="116" r="4.4" fill="#fff"/><circle cx="156" cy="116" r="4.4" fill="#fff"/>
  <circle cx="101" cy="128" r="2" fill="#fff" opacity=".8"/><circle cx="149" cy="128" r="2" fill="#fff" opacity=".8"/>
  <path d="M92 101 q12 -8 22 -2" stroke="#9A5E06" stroke-width="4" stroke-linecap="round" fill="none"/>
  <path d="M142 99 q10 -6 22 2" stroke="#9A5E06" stroke-width="4" stroke-linecap="round" fill="none"/>
  <ellipse cx="86" cy="146" rx="11" ry="6.5" fill="#FF8C6B" opacity=".5"/>
  <ellipse cx="170" cy="146" rx="11" ry="6.5" fill="#FF8C6B" opacity=".5"/>
  <path d="M112 146 q16 20 32 0 z" fill="#7A2E12"/>
  <path d="M119 155 q9 7 18 0 q-4 -6 -9 -5 q-5 -1 -9 5 z" fill="#FF7A6B"/>
  <path d="M111 146 q17 4 34 0" stroke="#7A2E12" stroke-width="3" stroke-linecap="round" fill="none"/>
</g></g>
<path transform="translate(206 52) scale(1.3)" d="M0 -10 C1.5 -2 2 -1.5 10 0 C2 1.5 1.5 2 0 10 C-1.5 2 -2 1.5 -10 0 C-2 -1.5 -1.5 -2 0 -10z" fill="#FFFFFF" opacity="1"/><path transform="translate(52 196) scale(0.8)" d="M0 -10 C1.5 -2 2 -1.5 10 0 C2 1.5 1.5 2 0 10 C-1.5 2 -2 1.5 -10 0 C-2 -1.5 -1.5 -2 0 -10z" fill="#FFE9C7" opacity="0.9"/>
</svg>
