import React, { useState, useEffect } from 'react'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Badge } from '../../components/ui/Badge'
import { SkeletonCard } from '../../components/ui/Skeleton'
import api from '../../services/api'

const TYPE_LABELS = {
  revision: 'Révision', vidange: 'Vidange', CT: 'Contrôle technique',
  freins: 'Freins', pneus: 'Pneus', autre: 'Autre',
}

export default function MonVehicule() {
  const [data, setData] = useState(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    api.get('/conducteur/mon-vehicule')
      .then(r => setData(r.data))
      .finally(() => setIsLoading(false))
  }, [])

  if (isLoading) {
    return (
      <Layout>
        <TopBar title="Mon véhicule" />
        <div className="p-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
          {[...Array(4)].map((_, i) => <SkeletonCard key={i} />)}
        </div>
      </Layout>
    )
  }

  if (!data?.vehicule) {
    return (
      <Layout>
        <TopBar title="Mon véhicule" />
        <div className="p-8 flex flex-col items-center justify-center min-h-64">
          <div className="glass-card p-12 text-center max-w-md">
            <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
              style={{ background: 'rgba(108,99,255,0.15)' }}>
              <svg className="w-8 h-8 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l1 1h1m8-1V7a1 1 0 011-1h2l3 4v5l-1 1h-1m-6 0h6" />
              </svg>
            </div>
            <h3 className="font-display text-lg font-semibold text-white mb-2">Aucun véhicule affecté</h3>
            <p className="text-slate-500 text-sm">Vous n'avez pas de véhicule affecté pour le moment. Contactez votre gestionnaire.</p>
          </div>
        </div>
      </Layout>
    )
  }

  const { vehicule, affectation, entretiens } = data

  return (
    <Layout>
      <TopBar title="Mon véhicule" subtitle="Informations et historique de votre véhicule affecté" />

      <div className="p-8 space-y-6 animate-slide-up">
        {/* Hero car card */}
        <div className="relative overflow-hidden rounded-2xl p-8"
          style={{ background: 'linear-gradient(135deg, #0F1628 0%, #1a1040 60%, #0d1a2e 100%)', border: '1px solid rgba(108,99,255,0.2)' }}>
          <div className="absolute top-0 right-0 w-80 h-80 rounded-full blur-3xl opacity-20"
            style={{ background: 'radial-gradient(circle, #6C63FF, transparent)' }} />
          <div className="absolute bottom-0 left-1/3 w-64 h-64 rounded-full blur-3xl opacity-10"
            style={{ background: 'radial-gradient(circle, #00D4FF, transparent)' }} />

          <div className="relative z-10 flex flex-col md:flex-row items-center gap-8">
            {/* Car SVG */}
            <div className="flex-shrink-0 animate-float">
              <svg viewBox="0 0 320 160" className="w-64" fill="none">
                <ellipse cx="160" cy="140" rx="130" ry="7" fill="rgba(108,99,255,0.2)" />
                <rect x="40" y="88" width="240" height="45" rx="8" fill="url(#heroCarGrad)" />
                <path d="M58 88 Q75 45 130 40 L190 40 Q245 45 262 88Z" fill="url(#heroRoofGrad)" />
                <path d="M82 87 Q88 51 122 47 L160 46 L160 87Z" fill="rgba(0,212,255,0.2)" stroke="rgba(0,212,255,0.4)" strokeWidth="1"/>
                <path d="M238 87 Q232 51 198 47 L160 46 L160 87Z" fill="rgba(0,212,255,0.2)" stroke="rgba(0,212,255,0.4)" strokeWidth="1"/>
                <circle cx="90" cy="130" r="22" fill="#111827" stroke="url(#heroWheelGrad)" strokeWidth="3"/>
                <circle cx="90" cy="130" r="10" fill="rgba(108,99,255,0.6)"/>
                <circle cx="230" cy="130" r="22" fill="#111827" stroke="url(#heroWheelGrad)" strokeWidth="3"/>
                <circle cx="230" cy="130" r="10" fill="rgba(108,99,255,0.6)"/>
                <rect x="38" y="97" width="16" height="7" rx="3.5" fill="rgba(0,212,255,0.9)" />
                <rect x="266" y="97" width="16" height="7" rx="3.5" fill="rgba(255,150,50,0.9)" />
                <line x1="160" y1="87" x2="160" y2="133" stroke="rgba(255,255,255,0.08)" strokeWidth="1"/>
                <defs>
                  <linearGradient id="heroCarGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#2D3B6E"/><stop offset="100%" stopColor="#1a2550"/>
                  </linearGradient>
                  <linearGradient id="heroRoofGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stopColor="#374185"/><stop offset="100%" stopColor="#2D3B6E"/>
                  </linearGradient>
                  <linearGradient id="heroWheelGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stopColor="#6C63FF"/><stop offset="100%" stopColor="#00D4FF"/>
                  </linearGradient>
                </defs>
              </svg>
            </div>

            {/* Info */}
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                <Badge variant={vehicule.statut} />
                <span className="text-xs text-slate-500">{vehicule.categorie || 'Non catégorisé'}</span>
              </div>
              <h2 className="font-display text-3xl font-bold text-white mb-1">
                {vehicule.marque} {vehicule.modele}
              </h2>
              <p className="font-mono text-xl font-bold mb-4 text-gradient">{vehicule.immatriculation}</p>

              <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                {[
                  { label: 'Année', value: vehicule.annee },
                  { label: 'Kilométrage', value: `${vehicule.kilometrage?.toLocaleString('fr-FR')} km` },
                  { label: 'En mission depuis', value: affectation?.dateDebut || '—' },
                ].map(({ label, value }) => (
                  <div key={label} className="glass-card p-3">
                    <p className="text-xs text-slate-500 mb-0.5">{label}</p>
                    <p className="text-sm font-semibold text-white">{value}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Maintenance history */}
        <div className="glass-card p-6">
          <h3 className="font-display font-semibold text-white mb-4">Historique d'entretiens</h3>
          {!entretiens || entretiens.length === 0 ? (
            <p className="text-slate-500 text-sm py-4">Aucun entretien enregistré.</p>
          ) : (
            <div className="space-y-3">
              {entretiens.map((e) => (
                <div key={e.id} className="flex items-center gap-4 p-4 rounded-xl border border-white/5"
                  style={{ background: 'rgba(255,255,255,0.03)' }}>
                  <div className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                    style={{ background: 'rgba(108,99,255,0.15)' }}>
                    <svg className="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0" />
                    </svg>
                  </div>
                  <div className="flex-1">
                    <p className="text-sm font-medium text-white">{TYPE_LABELS[e.type] || e.type}</p>
                    <p className="text-xs text-slate-500">
                      Réalisé le {e.dateRealise ? new Date(e.dateRealise).toLocaleDateString('fr-FR') : '—'}
                    </p>
                  </div>
                  {e.cout && <span className="text-sm font-semibold text-emerald-400">{parseFloat(e.cout).toLocaleString('fr-FR')} €</span>}
                  {e.dateProchaine && (
                    <span className={`text-xs px-2 py-1 rounded-full ${
                      new Date(e.dateProchaine) < new Date()
                        ? 'bg-red-500/15 text-red-400'
                        : 'bg-slate-500/15 text-slate-400'
                    }`}>
                      Prochain : {new Date(e.dateProchaine).toLocaleDateString('fr-FR')}
                    </span>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </Layout>
  )
}
