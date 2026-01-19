// resources/js/context/AuthContext.jsx
import { createContext, useContext, useEffect, useState } from 'react';
import axios from '../axios';


const AuthContext = createContext();
export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const getUser = async () => {
    try {
      await axios.get('/sanctum/csrf-cookie'); // 👈 Important pour auth:sanctum
      const res = await axios.get('/api/me');
      setUser(res.data);
    } catch (err) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    await axios.get('/sanctum/csrf-cookie'); // 👈 Nécessaire avant un POST avec cookies
    const res = await axios.post('/api/login', { email, password });
    setUser(res.data.user);
  };

  const register = async (formData) => {
    await axios.get('/sanctum/csrf-cookie');
    const res = await axios.post('/api/register', formData);
    setUser(res.data.user);
  };

  const logout = async () => {
    await axios.post('/api/logout');
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
