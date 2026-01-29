import '../../css/app.css';
import { useState } from 'react';
import api from 'axios';
import toast from 'react-hot-toast';
import { useAuth } from '../contexts/AuthContext';
import NavigationLayout from '../components/NavigationLayout';

const ProfilePage = () => {
    const { user } = useAuth();
    const [name, setName] = useState(user?.name || '');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);

    const updateProfile = async (e) => {
        e.preventDefault();
        setLoading(true);
        try {
            await api.put('/api/profile', { name, password }, { withCredentials: true });
            toast.success('✅ Profil mis à jour avec succès');
            setPassword('');
        } catch (error) {
            toast.error("❌ Erreur lors de la mise à jour");
        } finally {
            setLoading(false);
        }
    };

    return (
        <NavigationLayout>
            <div className="login-page">
            <div className="animated-bg"></div>

            <div className="login-form">
                <h2>👤 Modifier mon profil</h2>
                <form onSubmit={updateProfile} className="space-y-4 text-left">
                    <div className="input-group">
                        <input
                            type="text"
                            id="name"
                            placeholder=" "
                            value={name}
                            onChange={e => setName(e.target.value)}
                            required
                        />
                        <label htmlFor="name">Nom</label>
                    </div>

                    <div className="input-group">
                        <input
                            type="password"
                            id="password"
                            placeholder=" "
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                        />
                        <label htmlFor="password">Nouveau mot de passe</label>
                    </div>

                    <button type="submit" disabled={loading}>
                        {loading ? 'Mise à jour...' : 'Mettre à jour'}
                    </button>
                </form>
            </div>
        </div>
        </NavigationLayout>
        
    );
};

export default ProfilePage;
