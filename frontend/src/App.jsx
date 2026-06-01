import React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './hooks/useAuth'

import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import MonVehicule from './pages/Conducteur/MonVehicule'
import VehiculesList from './pages/Vehicules/VehiculesList'
import VehiculeDetail from './pages/Vehicules/VehiculeDetail'
import VehiculeForm from './pages/Vehicules/VehiculeForm'
import EntretiensList from './pages/Entretiens/EntretiensList'
import AlertesList from './pages/Alertes/AlertesList'
import AdminDashboard from './pages/Admin/AdminDashboard'
import UtilisateursList from './pages/Admin/UtilisateursList'
import CategoriesList from './pages/Admin/CategoriesList'

function ProtectedRoute({ children, requiredRole }) {
  const { isAuthenticated, hasRole } = useAuth()

  if (!isAuthenticated) return <Navigate to="/login" replace />
  if (requiredRole && !hasRole(requiredRole)) return <Navigate to="/dashboard" replace />

  return children
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/" element={<Navigate to="/dashboard" replace />} />

        <Route path="/dashboard" element={
          <ProtectedRoute><Dashboard /></ProtectedRoute>
        } />

        <Route path="/mon-vehicule" element={
          <ProtectedRoute><MonVehicule /></ProtectedRoute>
        } />

        <Route path="/vehicules" element={
          <ProtectedRoute><VehiculesList /></ProtectedRoute>
        } />
        <Route path="/vehicules/nouveau" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VehiculeForm /></ProtectedRoute>
        } />
        <Route path="/vehicules/:id" element={
          <ProtectedRoute><VehiculeDetail /></ProtectedRoute>
        } />
        <Route path="/vehicules/:id/modifier" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><VehiculeForm /></ProtectedRoute>
        } />

        <Route path="/entretiens" element={
          <ProtectedRoute><EntretiensList /></ProtectedRoute>
        } />

        <Route path="/alertes" element={
          <ProtectedRoute><AlertesList /></ProtectedRoute>
        } />

        <Route path="/admin" element={
          <ProtectedRoute requiredRole="ADMIN"><AdminDashboard /></ProtectedRoute>
        } />
        <Route path="/admin/utilisateurs" element={
          <ProtectedRoute requiredRole="ADMIN"><UtilisateursList /></ProtectedRoute>
        } />
        <Route path="/admin/categories" element={
          <ProtectedRoute requiredRole="GESTIONNAIRE"><CategoriesList /></ProtectedRoute>
        } />

        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </BrowserRouter>
  )
}
