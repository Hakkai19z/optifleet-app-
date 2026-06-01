import React from 'react'

export function Input({ label, error, id, className = '', required = false, type = 'text', ...props }) {
  const inputId = id || label?.toLowerCase().replace(/\s+/g, '-')
  return (
    <div className={className}>
      {label && (
        <label htmlFor={inputId} className="form-label">
          {label}{required && <span className="text-red-400 ml-1">*</span>}
        </label>
      )}
      <input id={inputId} type={type}
        className={`form-input ${error ? 'border-red-500/50 focus:border-red-500' : ''}`}
        {...props}
      />
      {error && <p className="mt-1.5 text-xs text-red-400">{error}</p>}
    </div>
  )
}
