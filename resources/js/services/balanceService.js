import axios from '../axios';

export async function getBalancesByPersonnel(personnelId) {
  const res = await axios.get(`/api/leave-balances/personnel/${personnelId}`);
  return res.data.data;
}

export async function getBalanceByPersonnelAndType(personnelId, typeId) {
  const res = await axios.get(`/api/leave-balances/personnel/${personnelId}/type/${typeId}`);
  return res.data;
}

export async function recalculateBalances(personnelId, typeId = null) {
  return axios.post('/api/leave-balances/recalculate', {
    personnel_id: personnelId,
    ...(typeId ? { leave_type_id: typeId } : {})
  });
}
