import React, { useEffect, useState } from "react";
import axios from "axios";
import { Card, Table, Badge, Spinner, Alert } from "react-bootstrap";

export default function PersonnelSoldes({ personnelId }) {
  const [soldes, setSoldes] = useState([]);
  const [personnel, setPersonnel] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!personnelId) return;

    setLoading(true);
    setError(null);

    axios
      .get(`/api/personnels/${personnelId}/soldes`)
      .then((res) => {
        setSoldes(res.data.soldes || []);
        setPersonnel(res.data.personnel || null);
      })
      .catch(() => {
        setError("Impossible de charger les soldes de congés.");
      })
      .finally(() => setLoading(false));
  }, [personnelId]);

  if (loading) {
    return <Spinner animation="border" />;
  }

  if (error) {
    return <Alert variant="danger">{error}</Alert>;
  }

  return (
    <Card className="shadow-sm mt-3">
      <Card.Header>
        <strong>Soldes de congés</strong>
        {personnel && (
          <div className="text-muted mt-1">
            {personnel.matricule} — {personnel.nom} {personnel.prenom}
          </div>
        )}
      </Card.Header>

      <Card.Body className="p-0">
        <Table bordered hover responsive className="mb-0">
          <thead className="table-light">
            <tr>
              <th>Type de congé</th>
              <th>Droit total</th>
              <th>Jours utilisés</th>
              <th>Solde restant</th>
            </tr>
          </thead>
          <tbody>
            {soldes.length === 0 && (
              <tr>
                <td colSpan="4" className="text-center text-muted">
                  Aucun solde disponible
                </td>
              </tr>
            )}

            {soldes.map((s) => (
              <tr key={s.leave_type_id}>
                <td>
                  {s.leave_type}{" "}
                  {s.est_exceptionnel && (
                    <Badge bg="warning" text="dark" className="ms-1">
                      Exceptionnel
                    </Badge>
                  )}
                </td>

                <td>{Number(s.droit_total).toFixed(2)} j</td>

                <td>{Number(s.jours_utilises).toFixed(2)} j</td>

                <td>
                  <Badge
                    bg={s.solde_restant >= 0 ? "success" : "danger"}
                  >
                    {Number(s.solde_restant).toFixed(2)} j
                  </Badge>
                </td>
              </tr>
            ))}
          </tbody>
        </Table>
      </Card.Body>
    </Card>
  );
}
