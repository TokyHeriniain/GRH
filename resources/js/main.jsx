import React from 'react';
import ReactDOM from 'react-dom/client';
import AppRoutes from './AppRoutes';
import { AuthProvider } from './contexts/AuthContext';
import '../css/app.css'; // tout en haut
import { Toaster } from 'react-hot-toast';
import 'bootstrap/dist/css/bootstrap.min.css';
import api from 'axios';
import 'react-toastify/dist/ReactToastify.css';
import { ToastContainer } from 'react-toastify';
import 'bootstrap-icons/font/bootstrap-icons.css';




// ✅ Axios global config
api.defaults.baseURL = 'http://localhost:8000';
api.defaults.withCredentials = true; // ⬅️ Pour que Sanctum gère les cookies

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <AuthProvider>
        <AppRoutes />
    </AuthProvider>
    <Toaster position="top-right" reverseOrder={false} />
    <ToastContainer position="top-right" autoClose={3000} />
  </React.StrictMode>
);
