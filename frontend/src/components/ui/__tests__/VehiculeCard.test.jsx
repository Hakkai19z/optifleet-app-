import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Badge } from '../Badge'
import { Card } from '../Card'

const VehiculeCard = ({ vehicule }) => (
  <Card>
    <h3>{vehicule.marque} {vehicule.modele}</h3>
    <p>{vehicule.immatriculation}</p>
    <Badge variant={vehicule.statut} />
    <p>{vehicule.kilometrage.toLocaleString('fr-FR')} km</p>
  </Card>
)

describe('VehiculeCard', () => {
  const mockVehicule = {
    id: 1,
    immatriculation: 'AB-123-CD',
    marque: 'Renault',
    modele: 'Clio',
    annee: 2022,
    kilometrage: 45000,
    statut: 'disponible',
    categorie: { libelle: 'Berline' },
  }

  it('renders vehicle information', () => {
    render(<VehiculeCard vehicule={mockVehicule} />)
    expect(screen.getByText('Renault Clio')).toBeInTheDocument()
    expect(screen.getByText('AB-123-CD')).toBeInTheDocument()
  })

  it('renders kilometrage formatted', () => {
    render(<VehiculeCard vehicule={mockVehicule} />)
    expect(screen.getByText(/45.000 km|45,000 km/)).toBeInTheDocument()
  })

  it('renders status badge', () => {
    render(<VehiculeCard vehicule={mockVehicule} />)
    expect(screen.getByText('Disponible')).toBeInTheDocument()
  })

  it('renders maintenance badge for maintenance status', () => {
    const maintenanceVehicule = { ...mockVehicule, statut: 'maintenance' }
    render(<VehiculeCard vehicule={maintenanceVehicule} />)
    expect(screen.getByText('Maintenance')).toBeInTheDocument()
  })
})
