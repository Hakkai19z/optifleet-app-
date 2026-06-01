import React from 'react'
import { Link } from 'react-router-dom'
import { Layout } from '../../components/layout/Layout'
import { TopBar } from '../../components/layout/TopBar'
import { Card } from '../../components/ui/Card'

export default function AdminDashboard() {
  return (
    <Layout>
      <TopBar title="Administration" subtitle="Gestion des utilisateurs et catégories" />
      <div className="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <Link to="/admin/utilisateurs">
          <Card accent="primary" className="hover:shadow-md transition-shadow cursor-pointer">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-primary/10 rounded-xl">
                <svg className="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
              <div>
                <h3 className="font-semibold text-dark">Utilisateurs</h3>
                <p className="text-sm text-gray-500">Gérer les comptes et les rôles</p>
              </div>
            </div>
          </Card>
        </Link>
        <Link to="/admin/categories">
          <Card accent="blue" className="hover:shadow-md transition-shadow cursor-pointer">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-blue-50 rounded-xl">
                <svg className="w-6 h-6 text-blue-fleet" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              <div>
                <h3 className="font-semibold text-dark">Catégories</h3>
                <p className="text-sm text-gray-500">Gérer les catégories de véhicules</p>
              </div>
            </div>
          </Card>
        </Link>
      </div>
    </Layout>
  )
}
