import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';
import NavigationLayout from './components/NavigationLayout';
import { toast } from 'react-toastify';

import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import Dashboard from './pages/Dashboard';
import ProfilePage from './pages/ProfilePage';
import UserManagement from './pages/UserManagement';
import Unauthorized from './pages/Unauthorized';
import GestionPersonnels from './pages/GestionPersonnels';
import StructureManagement from './pages/StructureManagement';
import GestionConge from './pages/Conges/GestionConge';
import DashboardPage from './pages/DashboardPage';
import PersonnelShow from './components/Personnels/PersonnelShow';
import ClotureAnnuelleRH from './pages/rh/ClotureAnnuelleRH';
import DashboardRH from './pages/rh/DashboardRH';
import JournalRH from './pages/rh/JournalRH';

const ProtectedRoute = ({ children, roles }) => {
    const { user } = useAuth();

    if (!user) return <Navigate to="/login" replace />;
    if (roles && !roles.includes(user.role.name)) return <Navigate to="/unauthorized" replace />;

    return children;
};

const AppRoutes = () => {
    const { user } = useAuth();

    return (
        <Router>
            {user && <NavigationLayout />}
            <Routes>
                {/* Public routes */}
                <Route path="/login" element={!user ? <LoginPage /> : <Navigate to="/dashboard" />} />
{/*                 <Route path="/register" element={!user ? <RegisterPage /> : <Navigate to="/dashboard" />} /> */}
                {/* Dashboard par rôle */}
                <Route path="/dashboard" element={
                    <ProtectedRoute roles={['Admin', 'RH']}>
                        <DashboardRH />
                    </ProtectedRoute>
                } />

                <Route path="/profil" element={
                    <ProtectedRoute>
                        <ProfilePage />
                    </ProtectedRoute>
                } />

                {/* Employé ou Manager */}                                          

                {/* Admin only */}
                <Route path="/admin/users" element={
                    <ProtectedRoute roles={['Admin']}>
                        <UserManagement />
                    </ProtectedRoute>
                } />     
                <Route path="/register" element={
                    <ProtectedRoute roles={['Admin']}>
                        <RegisterPage />
                    </ProtectedRoute>
                } />             

                {/* RH only */}
                <Route path="/rh" element={
                    <ProtectedRoute roles={['RH','Admin']}>
                        <GestionPersonnels /> 
                    </ProtectedRoute>
                } />  
                <Route path="/rh/journalRH" element={
                    <ProtectedRoute roles={['RH','Admin']}>
                        <JournalRH /> 
                    </ProtectedRoute>
                } />                                                        
                <Route path="/rh/conges" element={
                    <ProtectedRoute roles={['RH','Admin']}>
                        <GestionConge />
                    </ProtectedRoute>
                } />
                <Route path="/rh/cloture-annuelle" element={
                    <ProtectedRoute roles={['Admin']}>
                        <ClotureAnnuelleRH />
                    </ProtectedRoute>
                } />

                <Route path="/personnels/:id" element={
                    <ProtectedRoute roles={['RH','Admin']}>
                        <PersonnelShow />
                    </ProtectedRoute>
                }/>                

                <Route path="/structure" element={
                    <ProtectedRoute roles={['RH', 'Admin']}>
                        <StructureManagement />
                    </ProtectedRoute>
                } />


                {/* Création test RH */}
                <Route path="/admin/create-test-rh" element={
                    <ProtectedRoute roles={['Admin']}>
                        <div className="container mt-5">
                            <h2>Créer un compte RH test</h2>
                            <button
                                className="btn btn-success mt-3"
                                onClick={async () => {
                                    try {
                                        const res = await fetch("/api/admin/users/test-rh", {
                                            method: "POST",
                                            credentials: "include",
                                        });
                                        const data = await res.json();

                                        if (res.ok) {
                                            toast.success(data.message || "Compte RH créé avec succès");
                                        } else {
                                            toast.error(data.error || data.message || "Erreur lors de la création du compte RH");
                                        }
                                    } catch (err) {
                                        toast.error("Erreur réseau lors de la requête");
                                    }
                                }}
                            >
                                ➕ Créer Compte RH
                            </button>
                        </div>
                    </ProtectedRoute>
                } />

                {/* Unauthorized */}
                <Route path="/unauthorized" element={<Unauthorized />} />

                {/* Fallback */}
                <Route path="*" element={<Navigate to={user ? "/dashboard" : "/login"} />} />
            </Routes>
        </Router>
    );
};

export default AppRoutes;
