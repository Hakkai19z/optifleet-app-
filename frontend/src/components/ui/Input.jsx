import React from 'react'

export function Input({
  label,
  error,
  id,
  className = '',
  required = false,
  type = 'text',
  ...props
}) {
  const inputId = id || label?.toLowerCase().replace(/\s+/g, '-')

  return (
    <div className={className}>
      {label && (
        <label htmlFor={inputId} className="form-label">
          {label}
          {required && <span className="text-danger ml-1">*</span>}
        </label>
      )}
      <input
        id={inputId}
        type={type}
        className={`form-input ${error ? 'border-danger focus:border-danger focus:ring-danger/30' : ''}`}
        {...props}
      />
      {error && (
        <p className="mt-1 text-xs text-danger">{error}</p>
      )}
    </div>
  )
}
