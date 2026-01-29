// resources/js/context/AuthContext.jsx
import { createContext, useContext, useEffect, useState } from 'react';
import api from '../axios';


const AuthContext = createContext();
export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const getUser = async () => {
    try {
      await api.get('/sanctum/csrf-cookie'); // 👈 Important pour auth:sanctum
      const res = await api.get('/api/me');
      setUser(res.data);
    } catch (err) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    await api.get('/sanctum/csrf-cookie'); // 👈 Nécessaire avant un POST avec cookies
    const res = await api.post('/api/login', { email, password });
    setUser(res.data.user);
  };

  const register = async (formData) => {
    await api.get('/sanctum/csrf-cookie');
    const res = await api.post('/api/register', formData);
    setUser(res.data.user);
  };

  const logout = async () => {
    await api.post('/api/logout');
    setUser(null);
  };

  useEffect(() => {
    getUser();
  }, []);

  useEffect(() => {
    console.log("Utilisateur connecté :", user);
  }, [user]);


  return (
    <AuthContext.Provider value={{ user, loading, login, register, logout }}>
      {!loading && children}
    </AuthContext.Provider>
  );
};
