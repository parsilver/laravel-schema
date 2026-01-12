import { useState, useEffect, useCallback } from 'react'
import { ReactFlowProvider } from '@xyflow/react'
import Layout from './components/layout'
import { ERDiagram } from './components/er-diagram'

const DARK_MODE_KEY = 'laravel-schema:dark-mode'

export default function App() {
  const [darkMode, setDarkMode] = useState(() => {
    if (typeof window !== 'undefined') {
      // Check localStorage first
      const stored = localStorage.getItem(DARK_MODE_KEY)
      if (stored !== null) {
        return stored === 'true'
      }
      // Fall back to system preference
      return window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    return false
  })

  useEffect(() => {
    if (darkMode) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
    // Persist to localStorage
    localStorage.setItem(DARK_MODE_KEY, String(darkMode))
  }, [darkMode])

  const handleToggleDarkMode = useCallback(() => {
    setDarkMode(prev => !prev)
  }, [])

  return (
    <ReactFlowProvider>
      <Layout darkMode={darkMode} onToggleDarkMode={handleToggleDarkMode}>
        <ERDiagram />
      </Layout>
    </ReactFlowProvider>
  )
}
