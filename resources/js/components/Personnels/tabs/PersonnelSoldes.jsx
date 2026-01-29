import React, { useEffect, useState } from "react";
import api from "axios";
import { Card, Table, Badge, Spinner, Alert, OverlayTrigger, Tooltip } from "react-bootstrap";

export default function PersonnelSoldes({ personnelId, annee }) {
  const [soldes, setSoldes] = useState([]);
  const [personnel, setPersonnel] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const formatDays = (val) => Number(val).toFixed(2);

  useEffect(() => {
    if (!personnelId) return;

    setLoading(true);
    setError(null);

    const source = api.CancelToken.source();

    api
      .get(`/api/personnels/${personnelId}/soldes${annee ? `?annee=${annee}` : ""}`, {
        cancelToken: source.token,
      })
      .then((res) => {
        setSoldes(res.data.soldes || []);
        setPersonnel(res.data.personnel || null);
      })
      .catch((err) => {
        if (!api.isCancel(err)) {
          setError("Impossible de charger les soldes de congés.");
        }
      })
      .finally(() => setLoading(false));

    return () => {
      source.cancel("Opération annulée : personnel changé");
    };
  }, [personnelId, annee]);

  if (loading) return <Spinner animation="border" />;

  if (error) return <Alert variant="danger">{error}</Alert>;

  return (
    <Card className="shadow-sm mt-3">
      <Card.Header>
        <strong>Soldes de congés {annee ? `(${annee})` : ""}</strong>
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
                <td colSpan={4} className="text-center text-muted">
                  Aucun solde disponible
                </td>
              </tr>
            )}

            {soldes.map((s) => {
              // Couleur du badge selon solde
              const badgeBg =
                s.solde_restant > 0 ? "success" : s.solde_restant === 0 ? "warning" : "danger";

              return (
                <tr key={s.leave_type_id}>
                  <td>
                    {s.leave_type}
                    {/* Badge pour congé exceptionnel */}
                    {s.est_exceptionnel && (
                      <OverlayTrigger
                        placement="top"
                        overlay={<Tooltip>Congé exceptionnel</Tooltip>}
                      >
                        <Badge bg="warning" text="dark" className="ms-1">
                          !
                        </Badge>
                      </OverlayTrigger>
                    )}
                    {/* Badge limite pour Billet/Permission */}
                    {s.limite_jours > 0 && (
                      <OverlayTrigger
                        placement="top"
                        overlay={<Tooltip>Limite {s.limite_jours} j</Tooltip>}
                      >
                        <Badge bg="info" className="ms-1">
                          {s.limite_jours} j
                        </Badge>
                      </OverlayTrigger>
                    )}
                  </td>

                  <td>{formatDays(s.droit_total)} j</td>
                  <td>{formatDays(s.jours_utilises)} j</td>
                  <td>
                    <Badge bg={badgeBg}>
                      {formatDays(s.solde_restant)}
                      {s.limite_jours > 0 ? ` / ${s.limite_jours}` : ""} j
                    </Badge>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </Table>
      </Card.Body>
    </Card>
  );
}
