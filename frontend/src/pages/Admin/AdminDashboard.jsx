import React from 'react'
import { Link } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'

export default function AdminDashboard() {
  return (
    <Layout>
      <TopBar title="Administration" subtitle="Gestion des utilisateurs et catégories" />
      <div className="p-4 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-6 animate-slide-up">
        <Link to="/admin/utilisateurs">
          <div className="glass-card-hover p-6 cursor-pointer group">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 transition-all"
                style={{ background: 'rgba(108,99,255,0.15)', border: '1px solid rgba(108,99,255,0.2)' }}>
                <svg className="w-6 h-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
              <div>
                <h3 className="font-display font-semibold text-white">Utilisateurs</h3>
                <p className="text-sm text-slate-500">Gérer les comptes et les rôles</p>
              </div>
              <svg className="w-5 h-5 text-slate-600 ml-auto group-hover:text-violet-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </Link>

        <Link to="/admin/categories">
          <div className="glass-card-hover p-6 cursor-pointer group">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
                style={{ background: 'rgba(0,212,255,0.15)', border: '1px solid rgba(0,212,255,0.2)' }}>
                <svg className="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              <div>
                <h3 className="font-display font-semibold text-white">Catégories</h3>
                <p className="text-sm text-slate-500">Gérer les catégories de véhicules</p>
              </div>
              <svg className="w-5 h-5 text-slate-600 ml-auto group-hover:text-cyan-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </Link>
      </div>
    </Layout>
  )
}
