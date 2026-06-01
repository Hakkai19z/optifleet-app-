import React from 'react'

export function MapWidget({ latitude, longitude, adresse, className = '' }) {
  if (!latitude || !longitude) {
    return (
      <div className={`flex flex-col items-center justify-center py-8 text-slate-600 ${className}`}>
        <svg className="w-10 h-10 mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <p className="text-xs text-slate-500">Aucune localisation</p>
      </div>
    )
  }

  const lat = parseFloat(latitude)
  const lng = parseFloat(longitude)
  const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`
  const embedUrl = `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`

  return (
    <div className={`space-y-3 ${className}`}>
      {/* Map embed */}
      <div className="relative rounded-xl overflow-hidden border border-white/10" style={{ height: '200px' }}>
        <iframe
          src={embedUrl}
          width="100%"
          height="100%"
          style={{ border: 0, filter: 'invert(90%) hue-rotate(180deg) brightness(0.85) contrast(1.1)' }}
          allowFullScreen={false}
          loading="lazy"
          referrerPolicy="no-referrer-when-downgrade"
          title="Localisation du véhicule"
        />
        {/* Dark overlay gradient at bottom */}
        <div className="absolute bottom-0 left-0 right-0 h-8 pointer-events-none"
          style={{ background: 'linear-gradient(to top, rgba(17,24,39,0.8), transparent)' }} />
      </div>

      {/* Coordinates + link */}
      <div className="flex items-center justify-between">
        <div>
          {adresse && <p className="text-xs text-slate-400 mb-0.5">{adresse}</p>}
          <p className="text-xs font-mono text-slate-500">
            {lat.toFixed(6)}, {lng.toFixed(6)}
          </p>
        </div>
        <a
          href={mapsUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
          style={{ background: 'rgba(108,99,255,0.15)', color: '#A78BFA', border: '1px solid rgba(108,99,255,0.3)' }}
          onMouseEnter={e => e.currentTarget.style.background = 'rgba(108,99,255,0.25)'}
          onMouseLeave={e => e.currentTarget.style.background = 'rgba(108,99,255,0.15)'}
        >
          <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Google Maps
        </a>
      </div>
    </div>
  )
}
