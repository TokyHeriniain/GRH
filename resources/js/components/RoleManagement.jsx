import '../../css/app.css';
import { useState } from 'react';
import toast from 'react-hot-toast';
import axios from 'axios';

const roles = [
  { id: 1, name: 'Admin' },
  { id: 2, name: 'Manager' },
  { id: 3, name: 'Employe' },
];

const RoleManagement = ({ users }) => {
  const [loadingId, setLoadingId] = useState(null);

  const changeRole = async (userId, roleId) => {
    const roleName = roles.find(r => r.id === parseInt(roleId))?.name || 'ce rôle';
    if (!window.confirm(`Confirmer le changement de rôle en "${roleName}" ?`)) return;

    setLoadingId(userId);
    try {
      await axios.put(`/api/users/${userId}/role`, { role_id: roleId }, { withCredentials: true });
      toast.success('Rôle mis à jour avec succès.');
    } catch (error) {
      toast.error('Erreur lors de la mise à jour du rôle.');
    } finally {
      setLoadingId(null);
    }
  };

  return users.map(user => (
    <div key={user.id} className="user-role-item">
      <strong>{user.name}</strong> – {user.role.name}
      <select
        onChange={e => changeRole(user.id, e.target.value)}
        defaultValue={user.role.id}
        disabled={loadingId === user.id}
      >
        {roles.map(role => (
          <option key={role.id} value={role.id}>{role.name}</option>
        ))}
      </select>
    </div>
  ));
};

export default RoleManagement;
