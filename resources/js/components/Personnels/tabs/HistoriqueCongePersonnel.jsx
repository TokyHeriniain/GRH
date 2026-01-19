import React, { useEffect, useState } from "react";
import axios from "axios";
import dayjs from "dayjs";
import {
  Card,
  Table,
  Badge,
  Form,
  Row,
  Col,
  Button,
  Spinner,
  Pagination,
} from "react-bootstrap";
import AsyncSelect from "react-select/async";

export default function HistoriqueCongePersonnel({ personnelId }) {
  const [loading, setLoading] = useState(false);
  const [personnel, setPersonnel] = useState(null);
  const [conges, setConges] = useState([]);
  const [soldes, setSoldes] = useState([]);
  const [leaveTypesMap, setLeaveTypesMap] = useState({});

  const [filters, setFilters] = useState({
    status: "",
    leave_type_id: "",
    date_debut: "",
    date_fin: "",
  });

  const [pagination, setPagination] = useState({
    page: 1,
    perPage: 10,
    total: 0,
  });

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

  /* ================= FETCH ================= */
  const fetchHistorique = async (page = 1) => {
    if (!personnelId) return;
    setLoading(true);
    try {
      const res = await axios.get(`/api/personnels/${personnelId}/conges`, {
        params: { ...filters, page, per_page: pagination.perPage },
      });

      setPersonnel(res.data.personnel);
      setConges(res.data.conges || []);
      setSoldes(res.data.soldes || []);
      setPagination((p) => ({
        ...p,
        page,
        total: res.data.total || res.data.conges.length,
      }));
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHistorique();
  }, [personnelId, filters]);

  /* ================= LOADERS ================= */
  const loadLeaveTypes = async (q) => {
    const res = await axios.get(`/api/leave-types-search?q=${q || ""}`);
    const map = {};
    res.data.forEach((t) => {
      map[t.id] = t.nom;
    });
    setLeaveTypesMap(map);
    return res.data.map((t) => ({ value: t.id, label: t.nom }));
  };

  /* ================= RESET FILTERS ================= */
  const resetFilters = () => {
    setFilters({
      status: "",
      leave_type_id: "",
      date_debut: "",
      date_fin: "",
    });
  };

  /* ================= PAGINATION ================= */
  const handlePageChange = (page) => {
    fetchHistorique(page);
  };

  const renderPagination = () => {
    const pages = Math.ceil(pagination.total / pagination.perPage);
    if (pages <= 1) return null;
    let items = [];
    for (let i = 1; i <= pages; i++) {
      items.push(
        <Pagination.Item
          key={i}
          active={i === pagination.page}
          onClick={() => handlePageChange(i)}
        >
          {i}
        </Pagination.Item>
      );
    }
    return <Pagination>{items}</Pagination>;
  };

  if (!personnelId) {
    return (
      <Card className="p-4 text-center text-muted">
        Sélectionnez un personnel pour voir l’historique
      </Card>
    );
  }

  return (
    <div className="p-3">
      {loading && (
        <div className="text-center my-4">
          <Spinner animation="border" />
        </div>
      )}

      {personnel && (
        <Card className="mb-4 shadow-sm">
          <Card.Body>
            <h5>
              📜 Historique des congés – {personnel.matricule} |{" "}
              {personnel.nom} {personnel.prenom}
            </h5>
          </Card.Body>
        </Card>
      )}

      {/* ================= SOLDES ================= */}
      {soldes.length > 0 && (
        <Row className="mb-4">
          {soldes.map((s) => (
            <Col md={4} key={s.leave_type_id}>
              <Card className="shadow-sm">
                <Card.Body>
                  <strong>
                    {s.leave_type}{" "}
                    {s.est_exceptionnel && (
                      <Badge bg="warning" text="dark">
                        Exceptionnel
                      </Badge>
                    )}
                  </strong>
                  <div>Droit : {n(s.droit_total)} j</div>
                  <div>Utilisés : {n(s.jours_utilises)} j</div>
                  <Badge bg={s.solde_restant >= 0 ? "success" : "danger"}>
                    Solde : {n(s.solde_restant)} j
                  </Badge>
                </Card.Body>
              </Card>
            </Col>
          ))}
        </Row>
      )}

      {/* ================= FILTRES ================= */}
      <Card className="mb-3 shadow-sm">
        <Card.Body>
          <Form>
            <Row className="g-2 align-items-end">
              <Col md={3}>
                <Form.Label>Statut</Form.Label>
                <Form.Select
                  value={filters.status}
                  onChange={(e) =>
                    setFilters({ ...filters, status: e.target.value })
                  }
                >
                  <option value="">Tous</option>
                  <option value="en_attente">En attente</option>
                  <option value="approuve_manager">Approuvé Manager</option>
                  <option value="approuve_rh">Approuvé RH</option>
                  <option value="rejete">Rejeté</option>
                </Form.Select>
              </Col>

              <Col md={3}>
                <Form.Label>Type</Form.Label>
                <AsyncSelect
                  cacheOptions
                  defaultOptions
                  loadOptions={loadLeaveTypes}
                  isClearable
                  value={
                    filters.leave_type_id
                      ? {
                          value: filters.leave_type_id,
                          label:
                            leaveTypesMap[filters.leave_type_id] || "",
                        }
                      : null
                  }
                  onChange={(s) =>
                    setFilters({ ...filters, leave_type_id: s?.value || "" })
                  }
                />
              </Col>

              <Col md={3}>
                <Form.Label>Date début</Form.Label>
                <Form.Control
                  type="date"
                  value={filters.date_debut}
                  onChange={(e) =>
                    setFilters({ ...filters, date_debut: e.target.value })
                  }
                />
              </Col>

              <Col md={3}>
                <Form.Label>Date fin</Form.Label>
                <Form.Control
                  type="date"
                  value={filters.date_fin}
                  onChange={(e) =>
                    setFilters({ ...filters, date_fin: e.target.value })
                  }
                />
              </Col>

              <Col md={6} className="mt-2">
                <Button
                  variant="primary"
                  className="w-100"
                  onClick={() => fetchHistorique()}
                >
                  Appliquer
                </Button>
              </Col>
              <Col md={6} className="mt-2">
                <Button variant="secondary" className="w-100" onClick={resetFilters}>
                  Réinitialiser les filtres
                </Button>
              </Col>
            </Row>
          </Form>
        </Card.Body>
      </Card>

      {/* ================= TABLE ================= */}
      <Card className="shadow-sm">
        <Card.Body className="p-0">
          <Table bordered hover responsive className="mb-0">
            <thead className="table-primary">
              <tr>
                <th>Type</th>
                <th>Période</th>
                <th>Jours</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              {conges.length > 0 ? (
                conges.map((c) => (
                  <tr key={c.id}>
                    <td>
                      {c.leave_type?.nom}{" "}
                      {c.leave_type?.est_exceptionnel && (
                        <Badge bg="warning" text="dark">
                          Exceptionnel
                        </Badge>
                      )}
                    </td>
                    <td>
                      {dayjs(c.date_debut).format("DD/MM/YYYY")} {c.heure_debut}
                      <br />
                      {dayjs(c.date_fin).format("DD/MM/YYYY")} {c.heure_fin}
                    </td>
                    <td>{n(c.jours_utilises)} j</td>
                    <td>
                      <Badge bg={statusVariant(c.status)}>
                        {c.status.replace("_", " ")}
                      </Badge>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="text-center text-muted">
                    Aucun congé trouvé
                  </td>
                </tr>
              )}
            </tbody>
          </Table>
        </Card.Body>
      </Card>

      {/* ================= PAGINATION ================= */}
      <div className="mt-3 d-flex justify-content-center">
        {renderPagination()}
      </div>
    </div>
  );
}
