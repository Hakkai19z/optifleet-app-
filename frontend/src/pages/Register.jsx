import React, { useState, useEffect } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export default function Register() {
  const [nom, setNom] = useState('')
  const [prenom, setPrenom] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirm, setPasswordConfirm] = useState('')
  const [formError, setFormError] = useState('')
  const { register, isLoading, error, isAuthenticated, clearError } = useAuth()
  const navigate = useNavigate()

  useEffect(() => { if (isAuthenticated) navigate('/dashboard') }, [isAuthenticated, navigate])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setFormError('')
    clearError()

    if (password.length < 8) {
      setFormError('Le mot de passe doit contenir au moins 8 caractères')
      return
    }
    if (password !== passwordConfirm) {
      setFormError('Les mots de passe ne correspondent pas')
      return
    }

    try {
      await register(nom, prenom, email, password)
      navigate('/dashboard')
    } catch {}
  }

  return (
    <div className="min-h-screen flex" style={{ background: '#0B0F1A' }}>
      {/* Left panel - Hero */}
      <div className="hidden lg:flex flex-1 relative overflow-hidden items-center justify-center p-12"
        style={{ background: 'linear-gradient(135deg, #0F1628 0%, #1a1040 50%, #0d1a2e 100%)' }}>
        {/* Animated background circles */}
        <div className="absolute top-20 left-20 w-72 h-72 rounded-full blur-3xl opacity-20 animate-pulse"
          style={{ background: 'radial-gradient(circle, #6C63FF, transparent)' }} />
        <div className="absolute bottom-20 right-20 w-96 h-96 rounded-full blur-3xl opacity-15"
          style={{ background: 'radial-gradient(circle, #00D4FF, transparent)', animation: 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite' }} />

        <div className="relative z-10 text-center max-w-md">
          {/* Car SVG illustration */}
          <div className="mb-8 animate-float">
            <svg viewBox="0 0 400 200" className="w-full max-w-sm mx-auto" fill="none">
              {/* Car body */}
              <ellipse cx="200" cy="165" rx="150" ry="8" fill="rgba(108,99,255,0.2)" />
              <rect x="60" y="110" width="280" height="50" rx="8" fill="url(#carGrad)" />
              <path d="M80 110 Q100 60 160 55 L240 55 Q300 60 320 110Z" fill="url(#roofGrad)" />
              {/* Windows */}
              <path d="M108 108 Q115 68 155 63 L200 62 L200 108Z" fill="rgba(0,212,255,0.25)" stroke="rgba(0,212,255,0.5)" strokeWidth="1"/>
              <path d="M292 108 Q285 68 245 63 L200 62 L200 108Z" fill="rgba(0,212,255,0.25)" stroke="rgba(0,212,255,0.5)" strokeWidth="1"/>
              {/* Wheels */}
              <circle cx="120" cy="158" r="25" fill="#1a1a2e" stroke="url(#wheelGrad)" strokeWidth="3"/>
              <circle cx="120" cy="158" r="12" fill="rgba(108,99,255,0.5)"/>
              <circle cx="280" cy="158" r="25" fill="#1a1a2e" stroke="url(#wheelGrad)" strokeWidth="3"/>
              <circle cx="280" cy="158" r="12" fill="rgba(108,99,255,0.5)"/>
              {/* Headlights */}
              <rect x="58" y="120" width="20" height="8" rx="4" fill="rgba(0,212,255,0.8)" />
              <rect x="322" y="120" width="20" height="8" rx="4" fill="rgba(255,150,0,0.8)" />
              {/* Door line */}
              <line x1="200" y1="108" x2="200" y2="160" stroke="rgba(255,255,255,0.1)" strokeWidth="1"/>
              <defs>
                <linearGradient id="carGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stopColor="#2D3B6E"/>
                  <stop offset="100%" stopColor="#1a2550"/>
                </linearGradient>
                <linearGradient id="roofGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stopColor="#374185"/>
                  <stop offset="100%" stopColor="#2D3B6E"/>
                </linearGradient>
                <linearGradient id="wheelGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stopColor="#6C63FF"/>
                  <stop offset="100%" stopColor="#00D4FF"/>
                </linearGradient>
              </defs>
            </svg>
          </div>

          <h1 className="font-display text-4xl font-bold text-white mb-4">
            Rejoignez
            <span className="block text-gradient">OptiFleet</span>
          </h1>
          <p className="text-slate-400 text-lg leading-relaxed">
            Créez votre compte conducteur pour accéder à vos véhicules, réservations et documents.
          </p>
        </div>
      </div>

      {/* Right panel - Register form */}
      <div className="w-full lg:w-[440px] flex items-center justify-center p-8">
        <div className="w-full max-w-sm">
          <div className="flex items-center gap-3 mb-10">
            <div className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
              style={{ background: 'linear-gradient(135deg, #6C63FF, #00D4FF)' }}>
              <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l1 1h1m8-1V7a1 1 0 011-1h2l3 4v5l-1 1h-1m-6 0h6" />
              </svg>
            </div>
            <div>
              <p className="font-display font-bold text-white">OptiFleet</p>
              <p className="text-xs text-slate-500">Fleet Management Platform</p>
            </div>
          </div>

          <h2 className="font-display text-2xl font-bold text-white mb-2">Créer un compte</h2>
          <p className="text-slate-400 text-sm mb-8">Inscrivez-vous en tant que conducteur</p>

          {(formError || error) && (
            <div className="flex items-center gap-3 p-4 rounded-xl border border-red-500/20 mb-6"
              style={{ background: 'rgba(239,68,68,0.1)' }}>
              <span className="text-red-400 text-sm">⚠ {formError || error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="form-label">Prénom</label>
                <input type="text" value={prenom} onChange={(e) => setPrenom(e.target.value)}
                  className="form-input" placeholder="Pierre" required />
              </div>
              <div>
                <label className="form-label">Nom</label>
                <input type="text" value={nom} onChange={(e) => setNom(e.target.value)}
                  className="form-input" placeholder="Martin" required />
              </div>
            </div>
            <div>
              <label className="form-label">Adresse e-mail</label>
              <input type="email" value={email} onChange={(e) => setEmail(e.target.value)}
                className="form-input" placeholder="vous@exemple.fr" required />
            </div>
            <div>
              <label className="form-label">Mot de passe</label>
              <input type="password" value={password} onChange={(e) => setPassword(e.target.value)}
                className="form-input" placeholder="8 caractères minimum" required />
            </div>
            <div>
              <label className="form-label">Confirmer le mot de passe</label>
              <input type="password" value={passwordConfirm} onChange={(e) => setPasswordConfirm(e.target.value)}
                className="form-input" placeholder="••••••••" required />
            </div>

            <button type="submit" disabled={isLoading} className="btn-primary w-full mt-2 py-3 text-base">
              {isLoading ? (
                <span className="flex items-center justify-center gap-2">
                  <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  Création du compte...
                </span>
              ) : 'Créer mon compte →'}
            </button>
          </form>

          <div className="mt-8 pt-6 border-t border-white/5 text-center">
            <p className="text-sm text-slate-400">
              Déjà un compte ?{' '}
              <Link to="/login" className="text-violet-400 hover:text-violet-300 font-medium">
                Se connecter →
              </Link>
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
