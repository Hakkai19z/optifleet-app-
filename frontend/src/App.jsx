import React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './hooks/useAuth'

import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import MonVehicule from './pages/Conducteur/MonVehicule'
import VehiculesList from './pages/Vehicules/VehiculesList'
import VehiculeDetail from './pages/Vehicules/VehiculeDetail'
import VehiculeForm from './pages/Vehicules/VehiculeForm'
import VueFlotte from './pages/Gestionnaire/VueFlotte'
import AffectationsPage from './pages/Affectations/AffectationsPage'
import EntretiensList from './pages/Entretiens/EntretiensList'
import AlertesList from './pages/Alertes/AlertesList'
import AdminDashboard from './pages/Admin/AdminDashboard'
import UtilisateursList from './pages/Admin/UtilisateursList'
import CategoriesList from './pages/Admin/CategoriesList'

function ProtectedRoute({ children, requiredRole }) {
  const { isAuthenticated, hasRole } = useAuth()

  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (requiredRole && !hasRole(requiredRole)) return <Navigate to="/" replace />

  return children
}

// Redirige vers la page d'accueil adaptée au rôle
function HomeRedirect() {
  const { isAuthenticated, user } = useAuth()
  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (user?.role === 'CONDUCTEUR') return <Navigate to="/mon-vehicule" replace />
  return <Navigate to="/dashboard" replace />
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/" element={<HomeRedirect />} />

        <Route path="/dashboard" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><Dashboard /></ProtectedRoute>
        } />

        {/* Conducteur */}
        <Route path="/mon-vehicule" element={
          <ProtectedRoute><MonVehicule /></ProtectedRoute>
        } />

        {/* Flotte — gestionnaire/admin */}
        <Route path="/vehicules" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VehiculesList /></ProtectedRoute>
        } />
        <Route path="/vehicules/nouveau" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VehiculeForm /></ProtectedRoute>
        } />
        <Route path="/vehicules/:id" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VehiculeDetail /></ProtectedRoute>
        } />
        <Route path="/vehicules/:id/modifier" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VehiculeForm /></ProtectedRoute>
        } />

        <Route path="/vue-flotte" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VueFlotte /></ProtectedRoute>
        } />
        <Route path="/affectations" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><AffectationsPage /></ProtectedRoute>
        } />

        <Route path="/entretiens" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><EntretiensList /></ProtectedRoute>
        } />

        <Route path="/alertes" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><AlertesList /></ProtectedRoute>
        } />

        {/* Admin */}
        <Route path="/admin" element={
          <ProtectedRoute requiredRole="ADMIN"><AdminDashboard /></ProtectedRoute>
        } />
        <Route path="/admin/utilisateurs" element={
          <ProtectedRoute requiredRole="ADMIN"><UtilisateursList /></ProtectedRoute>
        } />
        <Route path="/admin/categories" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><CategoriesList /></ProtectedRoute>
        } />

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
