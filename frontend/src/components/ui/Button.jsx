import React from 'react'

export function Button({ variant = 'primary', children, className = '', disabled = false, type = 'button', onClick, size, ...props }) {
  const cls = variant === 'primary' ? 'btn-primary'
    : variant === 'danger' ? 'btn-danger'
    : variant === 'ghost' ? 'btn-ghost'
    : 'btn-secondary'
  const sizeCls = size === 'sm' ? 'text-xs px-3 py-1.5' : ''
  return (
    <button type={type} className={`${cls} ${sizeCls} ${className}`} disabled={disabled} onClick={onClick} {...props}>
      {children}
    </button>
  )
}
