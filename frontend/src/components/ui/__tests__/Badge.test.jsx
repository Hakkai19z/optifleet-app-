import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { Badge } from '../Badge'

describe('Badge', () => {
  it('renders disponible badge with correct label', () => {
    render(<Badge variant="disponible" />)
    expect(screen.getByText('Disponible')).toBeInTheDocument()
  })

  it('renders en_mission badge', () => {
    render(<Badge variant="en_mission" />)
    expect(screen.getByText('En mission')).toBeInTheDocument()
  })

  it('renders maintenance badge', () => {
    render(<Badge variant="maintenance" />)
    expect(screen.getByText('Maintenance')).toBeInTheDocument()
  })

  it('renders custom label when provided', () => {
    render(<Badge variant="disponible" label="Libre" />)
    expect(screen.getByText('Libre')).toBeInTheDocument()
  })

  it('applies correct CSS class for disponible', () => {
    const { container } = render(<Badge variant="disponible" />)
    expect(container.firstChild).toHaveClass('text-emerald-400')
  })

  it('applies correct CSS class for alerte', () => {
    const { container } = render(<Badge variant="alerte" />)
    expect(container.firstChild).toHaveClass('text-red-400')
  })
})
