import React, { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'
import { Layout } from '../components/layout/Layout'
import { TopBar } from '../components/layout/TopBar'
import { Card } from '../components/ui/Card'
import { SkeletonCard } from '../components/ui/Skeleton'
import { dashboardService } from '../services/dashboardService'

const STATUT_COLORS = {
  disponible: '#0F6E56',
  en_mission: '#185FA5',
  maintenance: '#854F0B',
  inactif: '#9CA3AF',
}

const STATUT_LABELS = {
  disponible: 'Disponible',
  en_mission: 'En mission',
  maintenance: 'Maintenance',
  inactif: 'Inactif',
}

function KpiCard({ title, value, subtitle, accent, icon }) {
  return (
    <Card accent={accent} className="flex items-start gap-4">
      <div className={`p-3 rounded-xl ${
        accent === 'primary' ? 'bg-primary/10' :
        accent === 'blue' ? 'bg-blue-50' :
        accent === 'teal' ? 'bg-teal-50' :
        accent === 'amber' ? 'bg-amber-50' : 'bg-gray-100'
      }`}>
        {icon}
      </div>
      <div>
        <p className="text-sm text-gray-500">{title}</p>
        <p className="text-2xl font-bold text-dark mt-0.5">{value}</p>
        {subtitle && <p className="text-xs text-gray-400 mt-0.5">{subtitle}</p>}
      </div>
    </Card>
  )
}

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    dashboardService.getStats()
      .then(setStats)
      .finally(() => setIsLoading(false))
  }, [])

  const pieData = stats ? Object.entries(stats.vehicules.parStatut).map(([name, value]) => ({
    name: STATUT_LABELS[name] || name,
    value,
    color: STATUT_COLORS[name] || '#9CA3AF',
  })) : []

  return (
    <Layout>
      <TopBar title="Tableau de bord" subtitle="Vue d'ensemble de la flotte" />
      <div className="p-8 space-y-8">
        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[...Array(4)].map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <KpiCard
              title="Total véhicules"
              value={stats?.vehicules.total ?? 0}
              subtitle="Ensemble de la flotte"
              accent="primary"
              icon={<svg className="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l1 1h1m8-1V7a1 1 0 011-1h2l3 4v5l-1 1h-1m-6 0h6" /></svg>}
            />
            <KpiCard
              title="Disponibles"
              value={stats?.vehicules.parStatut?.disponible ?? 0}
              subtitle={`Taux: ${stats?.vehicules.tauxDisponibilite ?? 0}%`}
              accent="teal"
              icon={<svg className="w-6 h-6 text-teal-fleet" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
            />
            <KpiCard
              title="Alertes actives"
              value={stats?.alertes.actives ?? 0}
              subtitle="À traiter"
              accent="amber"
              icon={<svg className="w-6 h-6 text-amber-fleet" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>}
            />
            <KpiCard
              title="Coût maintenance"
              value={`${(stats?.maintenance.cout12Mois ?? 0).toLocaleString('fr-FR')} €`}
              subtitle="12 derniers mois"
              accent="blue"
              icon={<svg className="w-6 h-6 text-blue-fleet" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
            />
          </div>
        )}

        {!isLoading && stats && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
              <h3 className="text-base font-semibold text-dark mb-4">Répartition par statut</h3>
              <div className="flex items-center gap-6">
                <ResponsiveContainer width="50%" height={180}>
                  <PieChart>
                    <Pie data={pieData} cx="50%" cy="50%" innerRadius={50} outerRadius={80} dataKey="value">
                      {pieData.map((entry, index) => (
                        <Cell key={index} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip formatter={(value, name) => [value, name]} />
                  </PieChart>
                </ResponsiveContainer>
                <div className="space-y-2">
                  {pieData.map((item) => (
                    <div key={item.name} className="flex items-center gap-2">
                      <div className="w-3 h-3 rounded-full flex-shrink-0" style={{ backgroundColor: item.color }} />
                      <span className="text-sm text-gray-600">{item.name}</span>
                      <span className="text-sm font-semibold text-dark ml-auto">{item.value}</span>
                    </div>
                  ))}
                </div>
              </div>
            </Card>

            <Card>
              <h3 className="text-base font-semibold text-dark mb-4">Accès rapide</h3>
              <div className="grid grid-cols-2 gap-3">
                {[
                  { to: '/vehicules', label: 'Gérer les véhicules', color: 'primary' },
                  { to: '/entretiens', label: 'Planifier un entretien', color: 'blue' },
                  { to: '/alertes', label: 'Voir les alertes', color: 'amber' },
                  { to: '/vehicules/nouveau', label: 'Nouveau véhicule', color: 'teal' },
                ].map((item) => (
                  <Link
                    key={item.to}
                    to={item.to}
                    className={`p-4 rounded-xl border-2 text-center text-sm font-medium transition-colors ${
                      item.color === 'primary' ? 'border-primary/20 text-primary hover:bg-primary hover:text-white' :
                      item.color === 'blue' ? 'border-blue-200 text-blue-fleet hover:bg-blue-fleet hover:text-white' :
                      item.color === 'amber' ? 'border-amber-200 text-amber-fleet hover:bg-amber-fleet hover:text-white' :
                      'border-teal-200 text-teal-fleet hover:bg-teal-fleet hover:text-white'
                    }`}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </Card>
          </div>
        )}
      </div>
    </Layout>
  )
}
