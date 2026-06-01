import React from 'react'

export function Select({ label, error, id, children, className = '', required = false, ...props }) {
  const inputId = id || label?.toLowerCase().replace(/\s+/g, '-')
  return (
    <div className={className}>
      {label && (
        <label htmlFor={inputId} className="form-label">
          {label}{required && <span className="text-red-400 ml-1">*</span>}
        </label>
      )}
      <select id={inputId} className={`form-input ${error ? 'border-red-500/50' : ''}`} {...props}>
        {children}
      </select>
      {error && <p className="mt-1.5 text-xs text-red-400">{error}</p>}
    </div>
  )
}
