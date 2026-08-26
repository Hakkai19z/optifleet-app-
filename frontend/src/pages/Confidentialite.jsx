import React from 'react'
import { Link } from 'react-router-dom'

// Politique de confidentialité (RGPD) — page publique.
export default function Confidentialite() {
  return (
    <div className="min-h-screen py-12 px-6" style={{ background: '#0B0F1A' }}>
      <div className="max-w-3xl mx-auto">
        <div className="flex items-center gap-3 mb-10">
          <div className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
            style={{ background: 'linear-gradient(135deg, #6C63FF, #00D4FF)' }}>
            <svg className="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <div>
            <p className="font-display font-bold text-white">OptiFleet</p>
            <p className="text-xs text-slate-500">Politique de confidentialité</p>
          </div>
        </div>

        <div className="glass-card p-8 space-y-6 text-slate-300 leading-relaxed">
          <h1 className="font-display text-2xl font-bold text-white">Politique de confidentialité &amp; RGPD</h1>
          <p className="text-sm text-slate-500">Dernière mise à jour : août 2026</p>

          <section className="space-y-2">
            <h2 className="font-semibold text-white">1. Responsable du traitement</h2>
            <p>OptiFleet est une application de gestion de flotte de véhicules. Les données sont
              traitées par l'organisation exploitant la plateforme, dans le cadre de la gestion
              de sa flotte et de ses conducteurs.</p>
          </section>

          <section className="space-y-2">
            <h2 className="font-semibold text-white">2. Données collectées</h2>
            <ul className="list-disc list-inside space-y-1">
              <li>Données d'identité : nom, prénom, adresse e-mail.</li>
              <li>Données d'authentification : mot de passe (stocké haché avec bcrypt, jamais en clair).</li>
              <li>Données d'usage : affectations de véhicules, réservations, pleins de carburant, signalements.</li>
            </ul>
          </section>

          <section className="space-y-2">
            <h2 className="font-semibold text-white">3. Finalités</h2>
            <p>Les données sont utilisées uniquement pour la gestion de la flotte : authentification,
              affectation des véhicules aux conducteurs, suivi des entretiens, réservations et
              statistiques internes. Aucune donnée n'est revendue à des tiers.</p>
          </section>

          <section className="space-y-2">
            <h2 className="font-semibold text-white">4. Base légale et durée de conservation</h2>
            <p>Le traitement repose sur l'intérêt légitime de l'organisation à gérer sa flotte.
              Les données sont conservées le temps de la relation puis supprimées ou anonymisées.</p>
          </section>

          <section className="space-y-2">
            <h2 className="font-semibold text-white">5. Vos droits</h2>
            <p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et
              d'effacement de vos données. Vous pouvez <strong>supprimer votre compte et l'ensemble
              de vos données personnelles</strong> directement depuis votre espace utilisateur
              (suppression définitive via l'API sécurisée <code>DELETE /api/auth/delete-account</code>).</p>
          </section>

          <section className="space-y-2">
            <h2 className="font-semibold text-white">6. Sécurité</h2>
            <p>Les mots de passe sont hachés (bcrypt), l'accès à l'API est protégé par jeton JWT,
              les échanges sont chiffrés en HTTPS en production, et l'accès aux données est
              restreint selon le rôle de chaque utilisateur (conducteur, gestionnaire, administrateur).</p>
          </section>

          <div className="pt-4 border-t border-white/5">
            <Link to="/login" className="text-violet-400 hover:text-violet-300 font-medium">← Retour à la connexion</Link>
          </div>
        </div>
      </div>
    </div>
  )
}
