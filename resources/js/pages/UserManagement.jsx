import '../../css/app.css';
import { useEffect, useState } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';

const UserManagement = () => {
    const [users, setUsers] = useState([]);

    // Charger les utilisateurs
    useEffect(() => {
        fetchUsers();
    }, []);

    const fetchUsers = async () => {
        try {
            const res = await axios.get('/api/users', { withCredentials: true });
            console.log('Réponse API /users:', res.data); // 👈 important
            setUsers(res.data.data ?? res.data); // fallback au cas où ce n'est pas paginé
        } catch (error) {
            toast.error("Erreur lors du chargement des utilisateurs.");
        }
    };



    // Changer le rôle d'un utilisateur
    const changeRole = async (id, role) => {
        try {
            await axios.put(`/api/users/${id}/role`, { role }, { withCredentials: true });
            toast.success("Rôle mis à jour !");
            fetchUsers(); // recharger les utilisateurs
        } catch (error) {
            toast.error("Échec de la mise à jour du rôle.");
        }
    };

    return (
        <div className="max-w-3xl mx-auto mt-6 p-4">
            <h2 className="text-xl font-bold mb-4">👥 Gestion des utilisateurs</h2>
            {Array.isArray(users) && users.length === 0 ? (
                <p>Aucun utilisateur trouvé.</p>
            ) : (
                Array.isArray(users) && users.map(user => (
                    <div
                        key={user.id}
                        className="border p-3 rounded mb-3 flex justify-between items-center bg-white shadow-sm"
                    >
                        <div>
                            <p className="font-semibold">{user.name}</p>
                            <p className="text-sm text-gray-600">{user.email}</p>
                            <p className="text-xs text-gray-500">Rôle actuel : <strong>{user.role.name}</strong></p>
                        </div>

                        <select
                            defaultValue={user.role.name}
                            onChange={e => changeRole(user.id, e.target.value)}
                            className="border px-2 py-1 rounded"
                        >
                            <option value="Admin">Admin</option>
                            <option value="Manager">Manager</option>
                            <option value="Employe">Employe</option>
                        </select>
                    </div>
                ))
            )}
        </div>
    );
};

export default UserManagement;
