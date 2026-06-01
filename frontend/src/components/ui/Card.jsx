import React from 'react'

export function Card({ children, className = '', glow, ...props }) {
  return (
    <div className={`glass-card p-6 ${glow ? 'glow-violet' : ''} ${className}`} {...props}>
      {children}
    </div>
  )
}
