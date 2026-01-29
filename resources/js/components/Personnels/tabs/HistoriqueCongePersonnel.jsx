import React, { useEffect, useState } from "react";
import api from "axios";
import dayjs from "dayjs";
import { Table, Badge, Spinner, OverlayTrigger, Tooltip } from "react-bootstrap";

export default function HistoriqueCongePersonnel({ personnelId }) {
  const [loading, setLoading] = useState(false);
  const [conges, setConges] = useState([]);

  const n = (v) => Number(v ?? 0).toFixed(2);

  const statusVariant = (status) => {
    switch (status) {
      case "approuve_rh":
        return "success";
      case "approuve_manager":
        return "info";
      case "rejete":
        return "danger";
      default:
        return "warning";
    }
  };

  const statusLabel = {
    en_attente: "En attente",
    approuve_manager: "Approuvé Manager",
    approuve_rh: "Approuvé RH",
    rejete: "Rejeté",
  };

  const fetchHistorique = async () => {
    if (!personnelId) return;
    setLoading(true);
    try {
      const res = await api.get(`/api/personnels/${personnelId}/conges`);
      setConges(res.data.conges || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHistorique();
  }, [personnelId]);

  const HolidaysBadge = ({ holidays }) => {
    if (!holidays || holidays.length === 0) return null;

    const tooltipContent = holidays
      .map((h) => `${h.title} (${dayjs(h.date).format("DD/MM/YYYY")})`)
      .join("\n");

    return (
      <OverlayTrigger
        placement="top"
        overlay={<Tooltip>{tooltipContent}</Tooltip>}
      >
        <Badge bg="warning" text="dark" className="ms-2" style={{ cursor: "pointer" }}>
          🎉 {holidays.length}
        </Badge>
      </OverlayTrigger>
    );
  };

  return (
    <div>
      {loading && (
        <div className="text-center my-3">
          <Spinner animation="border" />
        </div>
      )}

      <Table bordered hover responsive>
        <thead className="table-primary">
          <tr>
            <th>Type</th>
            <th>Période</th>
            <th className="text-end">Jours</th>
            <th>Statut</th>
            <th>Validé / saisi le</th>
            <th>Validé par</th>
          </tr>
        </thead>
        <tbody>
          {conges.length > 0 ? (
            conges.map((c) => (
              <tr key={c.id}>
                <td>{c.leave_type?.nom}</td>
                <td>
                  {dayjs(c.date_debut).format("DD/MM/YYYY")} {c.heure_debut}
                  <br />
                  {dayjs(c.date_fin).format("DD/MM/YYYY")} {c.heure_fin}
                </td>
                <td className="text-end">
                  {n(c.jours_utilises)} j
                  <HolidaysBadge holidays={c.impacted_holidays} />
                </td>
                <td>
                  <Badge bg={statusVariant(c.status)}>
                    {statusLabel[c.status]}
                  </Badge>
                </td>
                <td>
                  {dayjs(
                    c.status === "en_attente" ? c.created_at : c.updated_at
                  ).format("DD/MM/YYYY HH:mm")}
                </td>
                <td>{c.validated_by?.name || "-"}</td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan={6} className="text-center text-muted">
                Aucun congé trouvé
              </td>
            </tr>
          )}
        </tbody>
      </Table>
    </div>
  );
}
