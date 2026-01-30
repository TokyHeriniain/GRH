import React, { useEffect, useState } from "react";
import api from "../../axios";
import { Table, Badge } from "react-bootstrap";

export default function HistoriqueConge() {
  const [rows, setRows] = useState([]);

  useEffect(() => {
    load();
  }, []);

  const load = async () => {
    const res = await api.get("/api/me/conges");
    setRows(res.data.data || []);
  };

  const statusColor = (s) => {
    if (s === "approuve") return "success";
    if (s === "rejete") return "danger";
    return "warning";
  };

  return (
    <>
      <h5>Historique de mes congés</h5>

      <Table bordered hover>
        <thead>
          <tr>
            <th>Type</th>
            <th>Période</th>
            <th>Jours</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r) => (
            <tr key={r.id}>
              <td>{r.leave_type?.nom}</td>
              <td>{r.date_debut} → {r.date_fin}</td>
              <td>{r.jours}</td>
              <td>
                <Badge bg={statusColor(r.status)}>
                  {r.status}
                </Badge>
              </td>
            </tr>
          ))}
        </tbody>
      </Table>
    </>
  );
}
