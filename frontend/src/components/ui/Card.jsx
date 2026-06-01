import React from 'react'

const accentColors = {
  primary: 'border-primary',
  blue: 'border-blue-fleet',
  teal: 'border-teal-fleet',
  amber: 'border-amber-fleet',
  danger: 'border-danger',
  gray: 'border-gray-300',
}

export function Card({ children, className = '', accent, ...props }) {
  if (accent) {
    return (
      <div
        className={`bg-white rounded-xl shadow-sm border-l-4 ${accentColors[accent] || 'border-gray-300'} p-6 ${className}`}
        {...props}
      >
        {children}
      </div>
    )
  }

  return (
    <div
      className={`bg-white rounded-xl shadow-sm border border-gray-100 p-6 ${className}`}
      {...props}
    >
      {children}
    </div>
  )
}
