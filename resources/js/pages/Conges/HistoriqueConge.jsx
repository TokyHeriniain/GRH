import React, { useEffect, useState } from "react";
import axios from "axios";
import dayjs from "dayjs";
import AsyncSelect from "react-select/async";
import { Form, Row, Col, Button, Spinner, Badge } from "react-bootstrap";
import { toast } from "react-toastify";

const fmt = (v) =>
  Number.isFinite(Number(v)) ? Number(v).toFixed(2) : "—";

export default function HistoriqueConge({ reload }) {
  const [conges, setConges] = useState([]);
  const [loading, setLoading] = useState(false);

  const [filters, setFilters] = useState({
    personnel_id: "",
    leave_type_id: "",
    status: "",
    date_debut: "",
    date_fin: "",
  });

  const [currentPage, setCurrentPage] = useState(1);
  const [perPage] = useState(10);
  const [totalPages, setTotalPages] = useState(1);

  /* =========================
     FETCH HISTORIQUE
  ========================= */
  const fetchConges = async (page = 1) => {
    setLoading(true);
    try {
      const params = { page, per_page: perPage };
      Object.entries(filters).forEach(([k, v]) => v && (params[k] = v));

      const res = await axios.get("/api/conges/historique", { params });

      setConges(res.data.data || []);
      setCurrentPage(res.data.current_page || 1);
      setTotalPages(res.data.last_page || 1);
    } catch (e) {
      console.error(e);
      toast.error("Erreur lors du chargement de l'historique");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchConges(1);
  }, [reload, filters]);

  /* =========================
     ASYNC SELECT
  ========================= */
  const loadPersonnels = (q) =>
    axios.get(`/api/personnels-search?q=${q || ""}`).then((res) =>
      res.data.map((p) => ({
        value: p.id,
        label: `${p.matricule} - ${p.nom} ${p.prenom}`,
      }))
    );

  const loadLeaveTypes = (q) =>
    axios.get(`/api/leave-types-search?q=${q || ""}`).then((res) =>
      res.data.map((t) => ({
        value: t.id,
        label: t.nom,
      }))
    );

  /* =========================
     RENDER
  ========================= */
  return (
    <div className="p-4">
      <h4 className="mb-4">📜 Historique global des congés</h4>

      {/* 🔎 FILTRES */}
      <Form className="mb-4">
        <Row className="g-2">
          <Col md={6}>
            <Form.Label>Personnel</Form.Label>
            <AsyncSelect
              cacheOptions
              defaultOptions
              loadOptions={loadPersonnels}
              isClearable
              onChange={(s) =>
                setFilters((f) => ({ ...f, personnel_id: s?.value || "" }))
              }
            />
          </Col>

          <Col md={6}>
            <Form.Label>Type de congé</Form.Label>
            <AsyncSelect
              cacheOptions
              defaultOptions
              loadOptions={loadLeaveTypes}
              isClearable
              onChange={(s) =>
                setFilters((f) => ({ ...f, leave_type_id: s?.value || "" }))
              }
            />
          </Col>

          <Col md={3}>
            <Form.Label>Statut</Form.Label>
            <Form.Select
              value={filters.status}
              onChange={(e) =>
                setFilters((f) => ({ ...f, status: e.target.value }))
              }
            >
              <option value="">Tous</option>
              <option value="en_attente">En attente</option>
              <option value="approuve_manager">Approuvé manager</option>
              <option value="approuve_rh">Approuvé RH</option>
              <option value="rejete">Rejeté</option>
            </Form.Select>
          </Col>

          <Col md={3}>
            <Form.Label>Date début</Form.Label>
            <Form.Control
              type="date"
              value={filters.date_debut}
              onChange={(e) =>
                setFilters((f) => ({ ...f, date_debut: e.target.value }))
              }
            />
          </Col>

          <Col md={3}>
            <Form.Label>Date fin</Form.Label>
            <Form.Control
              type="date"
              value={filters.date_fin}
              onChange={(e) =>
                setFilters((f) => ({ ...f, date_fin: e.target.value }))
              }
            />
          </Col>

          <Col md={3} className="d-flex align-items-end">
            <Button
              variant="secondary"
              className="w-100"
              onClick={() =>
                setFilters({
                  personnel_id: "",
                  leave_type_id: "",
                  status: "",
                  date_debut: "",
                  date_fin: "",
                })
              }
            >
              Réinitialiser
            </Button>
          </Col>
        </Row>
      </Form>

      {/* 📋 TABLE */}
      {loading ? (
        <div className="text-center my-4">
          <Spinner animation="border" />
        </div>
      ) : (
        <div className="table-responsive">
          <table className="table table-bordered table-hover align-middle">
            <thead className="table-primary">
              <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Période</th>
                <th>Droit</th>
                <th>Utilisés</th>
                <th>Solde RH</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              {conges.length ? (
                conges.map((c) => (
                  <tr key={c.id}>
                    <td>{c.personnel?.matricule}</td>
                    <td>{c.personnel?.nom} {c.personnel?.prenom}</td>
                    <td>{c.leaveType?.nom}</td>
                    <td>
                      {dayjs(c.date_debut).format("DD/MM/YYYY")} {c.heure_debut}
                      <br />
                      {dayjs(c.date_fin).format("DD/MM/YYYY")} {c.heure_fin}
                    </td>
                    <td>{fmt(c.droit_total)} j</td>
                    <td>{fmt(c.jours_utilises)} j</td>
                    <td>
                      {c.status === "approuve_rh" ? (
                        <Badge bg="success">{fmt(c.solde_restant)} j</Badge>
                      ) : (
                        "—"
                      )}
                    </td>
                    <td>
                      <Badge
                        bg={
                          c.status === "approuve_rh"
                            ? "success"
                            : c.status === "rejete"
                            ? "danger"
                            : "warning"
                        }
                      >
                        {c.status.replaceAll("_", " ")}
                      </Badge>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="8" className="text-center">
                    Aucun congé trouvé
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* 📄 PAGINATION */}
      {totalPages > 1 && (
        <div className="d-flex gap-2 mt-3">
          {[...Array(totalPages)].map((_, i) => (
            <Button
              key={i}
              variant={currentPage === i + 1 ? "primary" : "outline-primary"}
              onClick={() => fetchConges(i + 1)}
            >
              {i + 1}
            </Button>
          ))}
        </div>
      )}
    </div>
  );
}
