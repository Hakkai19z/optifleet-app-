import React, { useState, useEffect } from 'react'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { SkeletonCard } from '../../components/ui/Skeleton'
import { statistiqueService } from '../../services/statistiqueService'
import { useToastStore } from '../../store/toastStore'

const tooltipStyle = { background: '#1C2437', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '12px', color: '#F1F5F9' }

function exportCsv(filename, rows) {
  if (!rows.length) return
  const headers = Object.keys(rows[0])
  const escape = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`
  const csv = [
    headers.join(';'),
    ...rows.map((r) => headers.map((h) => escape(r[h])).join(';')),
  ].join('\n')
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

function StatBox({ label, value, accent }) {
  return (
    <Card className="p-5">
      <p className="text-xs text-slate-500 uppercase tracking-widest mb-1">{label}</p>
      <p className="text-2xl font-display font-bold" style={{ color: accent }}>{value}</p>
    </Card>
  )
}

export default function Statistiques() {
  const { addToast } = useToastStore()
  const [stats, setStats] = useState(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    statistiqueService.getGlobales().then(setStats).finally(() => setIsLoading(false))
  }, [])

  const handleExportCouts = () => {
    if (!stats) return
    exportCsv('couts-flotte.csv', stats.coutsParMois.map((m) => ({
      Mois: m.label, Entretien: m.entretien, Carburant: m.carburant, Total: (m.entretien + m.carburant).toFixed(2),
    })))
    addToast('Export CSV généré')
  }

  const handleExportConso = () => {
    if (!stats) return
    exportCsv('consommation-flotte.csv', stats.consommationParVehicule.map((v) => ({
      Immatriculation: v.immatriculation, Modele: v.modele, 'Consommation_L_100km': v.consommation,
    })))
    addToast('Export CSV généré')
  }

  return (
    <Layout>
      <TopBar
        title="Statistiques"
        subtitle="Analyse des coûts et de la consommation de la flotte"
        actions={<Button variant="ghost" onClick={handleExportCouts}>⬇ Exporter les coûts (CSV)</Button>}
      />

      <div className="p-4 md:p-8 space-y-6">
        {isLoading || !stats ? (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {[...Array(3)].map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : (
          <>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <StatBox label="Carburant (12 mois)" value={`${stats.totaux.carburant12Mois.toLocaleString('fr-FR')} €`} accent="#00D4FF" />
              <StatBox label="Entretien (12 mois)" value={`${stats.totaux.entretien12Mois.toLocaleString('fr-FR')} €`} accent="#6C63FF" />
              <StatBox label="Documents à échéance" value={stats.totaux.documentsExpirant} accent="#F59E0B" />
            </div>

            {/* Coûts par mois */}
            <div className="glass-card p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="font-display font-semibold text-white">Coûts mensuels</h3>
                  <p className="text-xs text-slate-500 mt-0.5">Entretien et carburant sur 12 mois</p>
                </div>
                <Button variant="ghost" onClick={handleExportCouts}>⬇ CSV</Button>
              </div>
              <ResponsiveContainer width="100%" height={280}>
                <BarChart data={stats.coutsParMois}>
                  <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
                  <XAxis dataKey="label" tick={{ fill: '#94A3B8', fontSize: 11 }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fill: '#94A3B8', fontSize: 12 }} axisLine={false} tickLine={false} />
                  <Tooltip contentStyle={tooltipStyle} cursor={{ fill: 'rgba(255,255,255,0.03)' }} />
                  <Legend wrapperStyle={{ fontSize: 12 }} />
                  <Bar dataKey="entretien" name="Entretien" stackId="a" fill="#6C63FF" radius={[0, 0, 0, 0]} />
                  <Bar dataKey="carburant" name="Carburant" stackId="a" fill="#00D4FF" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>

            {/* Consommation par véhicule */}
            <div className="glass-card p-6">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="font-display font-semibold text-white">Consommation moyenne</h3>
                  <p className="text-xs text-slate-500 mt-0.5">Litres aux 100 km, par véhicule</p>
                </div>
                <Button variant="ghost" onClick={handleExportConso}>⬇ CSV</Button>
              </div>
              {stats.consommationParVehicule.length === 0 ? (
                <p className="text-sm text-slate-500 py-8 text-center">Pas assez de pleins pour calculer la consommation.</p>
              ) : (
                <ResponsiveContainer width="100%" height={Math.max(120, stats.consommationParVehicule.length * 48)}>
                  <BarChart data={stats.consommationParVehicule} layout="vertical">
                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
                    <XAxis type="number" tick={{ fill: '#94A3B8', fontSize: 12 }} axisLine={false} tickLine={false} unit=" L" />
                    <YAxis type="category" dataKey="immatriculation" tick={{ fill: '#94A3B8', fontSize: 11 }} axisLine={false} tickLine={false} width={90} />
                    <Tooltip contentStyle={tooltipStyle} cursor={{ fill: 'rgba(255,255,255,0.03)' }} formatter={(v) => [`${v} L/100km`, 'Consommation']} />
                    <Bar dataKey="consommation" name="L/100km" fill="#10B981" radius={[0, 4, 4, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              )}
            </div>

            {/* Documents expirant */}
            {stats.documentsExpirant.length > 0 && (
              <div className="glass-card p-6">
                <h3 className="font-display font-semibold text-white mb-4">Documents à renouveler</h3>
                <div className="space-y-2">
                  {stats.documentsExpirant.map((d) => (
                    <div key={d.id} className="flex items-center justify-between p-3 rounded-xl border border-white/5">
                      <div className="flex items-center gap-3">
                        <span className="font-mono font-bold text-violet-400 text-sm">{d.vehicule}</span>
                        <span className="text-slate-300 text-sm capitalize">{d.type.replace('_', ' ')}</span>
                      </div>
                      <span className="text-xs font-semibold px-2.5 py-1 rounded-full"
                        style={d.expire
                          ? { color: '#EF4444', background: 'rgba(239,68,68,0.15)' }
                          : { color: '#F59E0B', background: 'rgba(245,158,11,0.15)' }}>
                        {d.expire ? `Expiré (${Math.abs(d.jours)} j)` : `${d.jours} j restants`}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </Layout>
  )
}
