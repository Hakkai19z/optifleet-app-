import React from 'react'
import { Sidebar } from './Sidebar'
import { ToastContainer } from '../ui/Toast'

export function Layout({ children }) {
  return (
    <div className="flex h-screen overflow-hidden bg-gray-50">
      <Sidebar />
      <main className="flex-1 overflow-y-auto">
        {children}
      </main>
      <ToastContainer />
    </div>
  )
}
