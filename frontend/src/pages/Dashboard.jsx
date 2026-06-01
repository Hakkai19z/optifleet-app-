import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'
import { Layout } from '../components/layout/Layout'
import { TopBar } from '../components/layout/TopBar'
import { SkeletonCard } from '../components/ui/Skeleton'
import { dashboardService } from '../services/dashboardService'
import { useAuth } from '../hooks/useAuth'

const STATUT_CONFIG = {
  disponible: { color: '#10B981', label: 'Disponible' },
  en_mission: { color: '#3B82F6', label: 'En mission' },
  maintenance: { color: '#F59E0B', label: 'Maintenance' },
  inactif: { color: '#6B7280', label: 'Inactif' },
}

function StatCard({ title, value, subtitle, icon, gradient, trend }) {
  return (
    <div className="glass-card-hover p-6 group relative overflow-hidden">
      <div className="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
        style={{ background: `radial-gradient(circle at 80% 20%, ${gradient}15, transparent 60%)` }} />
      <div className="relative z-10">
        <div className="flex items-start justify-between mb-4">
          <div className="w-11 h-11 rounded-2xl flex items-center justify-center"
            style={{ background: `${gradient}20`, border: `1px solid ${gradient}30` }}>
            <span style={{ color: gradient }}>{icon}</span>
          </div>
          {trend !== undefined && (
            <span className={`text-xs font-semibold px-2 py-1 rounded-full ${trend >= 0 ? 'bg-emerald-500/15 text-emerald-400' : 'bg-red-500/15 text-red-400'}`}>
              {trend >= 0 ? '↑' : '↓'} {Math.abs(trend)}%
            </span>
          )}
        </div>
        <p className="text-3xl font-display font-bold text-white mb-1">{value}</p>
        <p className="text-sm font-medium" style={{ color: 'rgba(241,245,249,0.8)' }}>{title}</p>
        {subtitle && <p className="text-xs text-slate-500 mt-1">{subtitle}</p>}
      </div>
    </div>
  )
}

const mockAreaData = [
  { month: 'Jan', cout: 2400 }, { month: 'Fév', cout: 1800 },
  { month: 'Mar', cout: 3200 }, { month: 'Avr', cout: 2800 },
  { month: 'Mai', cout: 3600 }, { month: 'Jun', cout: 4200 },
]

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const { user, hasRole } = useAuth()

  useEffect(() => {
    dashboardService.getStats().then(setStats).finally(() => setIsLoading(false))
  }, [])

  const pieData = stats ? Object.entries(stats.vehicules.parStatut).map(([name, value]) => ({
    name: STATUT_CONFIG[name]?.label || name,
    value,
    color: STATUT_CONFIG[name]?.color || '#6B7280',
  })) : []

  const total = stats?.vehicules.total || 0

  return (
    <Layout>
      <TopBar
        title={`Bonjour, ${user?.prenom} 👋`}
        subtitle="Voici l'état de votre flotte en temps réel"
      />

      <div className="p-4 md:p-8 space-y-8 animate-slide-up">
        {/* KPI Cards */}
        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            {[...Array(4)].map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            <StatCard
              title="Véhicules totaux"
              value={total}
              subtitle="Ensemble de la flotte"
              gradient="#6C63FF"
              trend={5}
              icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l1 1h1m8-1V7a1 1 0 011-1h2l3 4v5l-1 1h-1m-6 0h6" /></svg>}
            />
            <StatCard
              title="Disponibles"
              value={stats?.vehicules.parStatut?.disponible ?? 0}
              subtitle={`Taux : ${stats?.vehicules.tauxDisponibilite ?? 0}%`}
              gradient="#10B981"
              trend={2}
              icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
            />
            <StatCard
              title="Alertes actives"
              value={stats?.alertes.actives ?? 0}
              subtitle="À traiter en priorité"
              gradient="#F59E0B"
              trend={-10}
              icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>}
            />
            <StatCard
              title="Coût maintenance"
              value={`${(stats?.maintenance.cout12Mois ?? 0).toLocaleString('fr-FR')} €`}
              subtitle="12 derniers mois"
              gradient="#00D4FF"
              icon={<svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>}
            />
          </div>
        )}

        {/* Charts Row */}
        {!isLoading && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Area Chart */}
            <div className="glass-card p-6 lg:col-span-2">
              <div className="flex items-center justify-between mb-6">
                <div>
                  <h3 className="font-display font-semibold text-white">Coûts de maintenance</h3>
                  <p className="text-xs text-slate-500 mt-0.5">Évolution sur 6 mois</p>
                </div>
                <span className="text-xs px-3 py-1 rounded-full font-medium"
                  style={{ background: 'rgba(108,99,255,0.15)', color: '#A78BFA' }}>2026</span>
              </div>
              <ResponsiveContainer width="100%" height={200}>
                <AreaChart data={mockAreaData}>
                  <defs>
                    <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor="#6C63FF" stopOpacity={0.4}/>
                      <stop offset="100%" stopColor="#6C63FF" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.05)" />
                  <XAxis dataKey="month" tick={{ fill: '#94A3B8', fontSize: 12 }} axisLine={false} tickLine={false} />
                  <YAxis tick={{ fill: '#94A3B8', fontSize: 12 }} axisLine={false} tickLine={false} />
                  <Tooltip contentStyle={{ background: '#1C2437', border: '1px solid rgba(255,255,255,0.1)', borderRadius: '12px', color: '#F1F5F9' }} />
                  <Area type="monotone" dataKey="cout" stroke="#6C63FF" strokeWidth={2} fill="url(#areaGrad)" />
                </AreaChart>
              </ResponsiveContainer>
            </div>

            {/* Pie Chart */}
            <div className="glass-card p-6">
              <h3 className="font-display font-semibold text-white mb-1">Répartition</h3>
              <p className="text-xs text-slate-500 mb-6">Par statut</p>
              <div className="flex justify-center mb-4">
                <div className="relative">
                  <PieChart width={160} height={160}>
                    <Pie data={pieData} cx={80} cy={80} innerRadius={50} outerRadius={75} dataKey="value" strokeWidth={0}>
                      {pieData.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                    </Pie>
                  </PieChart>
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <p className="font-display text-2xl font-bold text-white">{total}</p>
                      <p className="text-xs text-slate-500">Total</p>
                    </div>
                  </div>
                </div>
              </div>
              <div className="space-y-2">
                {pieData.map((item) => (
                  <div key={item.name} className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <div className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: item.color }} />
                      <span className="text-sm text-slate-400">{item.name}</span>
                    </div>
                    <span className="text-sm font-semibold text-white">{item.value}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Quick Actions — only for GESTIONNAIRE+ */}
        {hasRole('GESTIONNAIRE') && !isLoading && (
          <div className="glass-card p-6">
            <h3 className="font-display font-semibold text-white mb-4">Accès rapide</h3>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
              {[
                { to: '/vehicules/nouveau', label: '+ Nouveau véhicule', color: '#6C63FF' },
                { to: '/vehicules', label: 'Voir la flotte', color: '#00D4FF' },
                { to: '/entretiens', label: 'Entretiens', color: '#10B981' },
                { to: '/alertes', label: 'Alertes', color: '#F59E0B' },
              ].map((item) => (
                <Link key={item.to} to={item.to}
                  className="p-4 rounded-xl border border-white/5 text-center text-sm font-medium transition-all hover:-translate-y-0.5"
                  onMouseEnter={(e) => { e.currentTarget.style.borderColor = item.color + '50'; e.currentTarget.style.background = item.color + '10' }}
                  onMouseLeave={(e) => { e.currentTarget.style.borderColor = 'rgba(255,255,255,0.05)'; e.currentTarget.style.background = '' }}>
                  <span style={{ color: item.color }}>{item.label}</span>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>
    </Layout>
  )
}
