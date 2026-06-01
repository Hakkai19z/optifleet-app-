import React from 'react'

export function Skeleton({ className = '' }) {
  return <div className={`animate-pulse rounded-xl bg-white/5 ${className}`} />
}

export function SkeletonCard() {
  return (
    <div className="glass-card p-6 space-y-3">
      <Skeleton className="h-4 w-1/2" />
      <Skeleton className="h-8 w-3/4" />
      <Skeleton className="h-3 w-full" />
    </div>
  )
}

export function SkeletonTable({ rows = 5 }) {
  return (
    <div className="space-y-3 p-6">
      {Array.from({ length: rows }).map((_, i) => <Skeleton key={i} className="h-14 w-full" />)}
    </div>
  )
}
