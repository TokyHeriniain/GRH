import React, { useEffect, useState, useCallback } from "react";
import api from "../../axios"; // instance Axios centralisée
import Select from "react-select";
import debounce from "lodash.debounce";
import { Table, Button, Form, Row, Col, Badge, Alert, Card } from "react-bootstrap";

const n = (v) => Number(v ?? 0).toFixed(2);

const defaultFilters = {
  direction: null,
  service: null,
  search: "",
};

export default function ReliquatsEnCours({ reload }) {
  const [rows, setRows] = useState([]);
  const [annee, setAnnee] = useState(new Date().getFullYear());
  const [filters, setFilters] = useState(defaultFilters);
  const [directions, setDirections] = useState([]);
  const [services, setServices] = useState([]);
  const [totaux, setTotaux] = useState(null);
  const [alertThreshold, setAlertThreshold] = useState(5);

  // ================= FETCH DATA =================
  const fetchData = async (f = filters) => {
    try {
      const params = {
        search: f.search,
        direction: f.direction?.value || "",
        service: f.service?.value || "",
      };

      const res = await api.get("/api/rh/reliquats", { params });

      setRows(Array.isArray(res.data?.data) ? res.data.data : []);
      setAnnee(res.data?.annee ?? new Date().getFullYear());
      setTotaux(res.data?.totaux ?? null);
    } catch (err) {
      console.error("Erreur fetchData", err);
      setRows([]);
      setTotaux(null);
    }
  };

  // ================= DEBOUNCED SEARCH =================
  const debouncedFetch = useCallback(
    debounce((f) => fetchData(f), 400),
    []
  );

  // ================= LOAD OPTIONS =================
  useEffect(() => {
    const loadDirections = async () => {
      try {
        const res = await api.get("/api/directions");

        const data = Array.isArray(res.data)
          ? res.data
          : Array.isArray(res.data?.data)
          ? res.data.data
          : [];

        setDirections(data.map((d) => ({
          value: d.nom,
          label: d.nom,
        })));
      } catch (err) {
        console.error("Erreur directions", err);
        setDirections([]);
      }
    };


    const loadServices = async () => {
      try {
        const res = await api.get("/api/services");

        const data = Array.isArray(res.data)
          ? res.data
          : Array.isArray(res.data?.data)
          ? res.data.data
          : [];

        setServices(data.map((s) => ({
          value: s.nom,
          label: s.nom,
        })));
      } catch (err) {
        console.error("Erreur services", err);
        setServices([]);
      }
    };


    loadDirections();
    loadServices();
  }, []);

  // ================= INITIAL LOAD =================
  useEffect(() => {
    fetchData();
  }, [reload]);

  // ================= FILTER HANDLERS =================
  const onSearchChange = (e) => {
    const f = { ...filters, search: e.target.value };
    setFilters(f);
    debouncedFetch(f);
  };

  const onSelectChange = (key, value) => {
    const f = { ...filters, [key]: value };
    setFilters(f);
    fetchData(f);
  };

  const resetFilters = () => {
    setFilters(defaultFilters);
    fetchData(defaultFilters);
  };

  // ================= EXPORT =================
  const buildExportParams = () =>
    new URLSearchParams({
      search: filters.search || "",
      direction: filters.direction?.value || "",
      service: filters.service?.value || "",
      annee,
    }).toString();

  const exportFile = (type) => {
    window.open(`/api/rh/reliquats/export/${type}?${buildExportParams()}`, "_blank");
  };

  return (
    <>
      <h5 className="mb-3">
        Reliquats congés en cours — <Badge bg="secondary">{annee}</Badge>
      </h5>

      {/* ================= SEUIL RH ================= */}
      <Form.Group className="mb-3" style={{ maxWidth: 220 }}>
        <Form.Label>⚠️ Seuil alerte RH (jours)</Form.Label>
        <Form.Control
          type="number"
          min={0}
          value={alertThreshold}
          onChange={(e) => setAlertThreshold(Number(e.target.value))}
        />
      </Form.Group>

      {/* ================= TOTAUX GLOBAUX ================= */}
      {totaux && (
        <Alert variant="info" className="fw-semibold">
          Total agents : <strong>{totaux.agents}</strong> — Reliquats cumulés :{" "}
          <strong className={totaux.reliquats <= alertThreshold ? "text-danger" : ""}>
            {n(totaux.reliquats)} jours
          </strong>
        </Alert>
      )}

      {/* ================= FILTRES ================= */}
      <Row className="mb-3 g-2 align-items-end">
        <Col md={4}>
          <Form.Label>Recherche</Form.Label>
          <Form.Control
            placeholder="Matricule / Nom / Prénom"
            value={filters.search}
            onChange={onSearchChange}
          />
        </Col>

        <Col md={3}>
          <Form.Label>Direction</Form.Label>
          <Select
            isClearable
            options={directions}
            value={filters.direction}
            onChange={(v) => onSelectChange("direction", v)}
            placeholder="Toutes"
          />
        </Col>

        <Col md={3}>
          <Form.Label>Service</Form.Label>
          <Select
            isClearable
            options={services}
            value={filters.service}
            onChange={(v) => onSelectChange("service", v)}
            placeholder="Tous"
          />
        </Col>

        <Col md={2} className="d-flex gap-2">
          <Button variant="outline-secondary" onClick={resetFilters}>
            Réinitialiser
          </Button>
        </Col>
      </Row>

      {/* ================= EXPORT ================= */}
      <div className="d-flex gap-2 mb-3">
        <Button variant="success" onClick={() => exportFile("excel")}>
          Export Excel
        </Button>
        <Button variant="danger" onClick={() => exportFile("pdf")}>
          Export PDF
        </Button>
        <Button
          variant="outline-danger"
          onClick={() =>
            window.open(`/api/rh/reliquats/export/pdf?preview=1&${buildExportParams()}`, "_blank")
          }
        >
          👁️ Aperçu PDF
        </Button>
        <Button
          variant="primary"
          onClick={() =>
            window.open(`/api/rh/reliquats/export/excel-multisheet?${buildExportParams()}`, "_blank")
          }
        >
          📁 Export Excel par direction
        </Button>
      </div>

      {/* ================= TABLE ================= */}
      <Table bordered hover size="sm" className="align-middle">
        <thead className="table-light">
          <tr>
            <th>Matricule</th>
            <th>Personnel</th>
            <th>Direction</th>
            <th>Service</th>
            <th className="text-end">Reliquat</th>
          </tr>
        </thead>
        <tbody>
          {Array.isArray(rows) && rows.length === 0 && (
            <tr>
              <td colSpan={5} className="text-center text-muted">
                Aucune donnée
              </td>
            </tr>
          )}

          {Array.isArray(rows) && rows.map((r) => (
            <tr key={r.matricule}>
              <td>{r.matricule}</td>
              <td>{r.personnel}</td>
              <td>{r.direction}</td>
              <td>{r.service}</td>
              <td
                className={`text-end fw-bold ${
                  r.reliquat <= alertThreshold ? "text-danger bg-warning-subtle" : ""
                }`}
              >
                {n(r.reliquat)}
                {r.reliquat <= alertThreshold && (
                  <span className="ms-2 badge bg-danger">⚠</span>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </Table>

      {/* ================= TOTAUX PAR DIRECTION ================= */}
      {totaux?.parDirection && (
        <Card className="mb-3 shadow-sm">
          <Card.Header>📂 Totaux par direction</Card.Header>
          <Table size="sm" bordered>
            <thead>
              <tr>
                <th>Direction</th>
                <th>Agents</th>
                <th className="text-end">Reliquats</th>
              </tr>
            </thead>
            <tbody>
              {Object.entries(totaux.parDirection).map(([dir, v]) => (
                <tr key={dir}>
                  <td>{dir || "—"}</td>
                  <td>{v.agents}</td>
                  <td className={`text-end fw-bold ${v.reliquat <= alertThreshold ? "text-danger" : ""}`}>
                    {n(v.reliquat)}
                  </td>
                </tr>
              ))}
            </tbody>
          </Table>
        </Card>
      )}
    </>
  );
}
