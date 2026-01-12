import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './app'
import { SchemaProvider } from './context/schema-context'
import './styles/app.css'

const rootElement = document.getElementById('app')

if (rootElement) {
  ReactDOM.createRoot(rootElement).render(
    <React.StrictMode>
      <SchemaProvider>
        <App />
      </SchemaProvider>
    </React.StrictMode>
  )
}
