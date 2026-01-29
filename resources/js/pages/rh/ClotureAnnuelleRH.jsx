import React, { useEffect, useState, useMemo } from "react";
import api from "axios";
import { Table, Button, Alert, Spinner, Badge, Tabs, Tab, Form } from "react-bootstrap";
import { toast } from "react-toastify";
import NavigationLayout from "../../components/NavigationLayout";
import ClotureTable from "./ClotureTable";

export default function ClotureAnnuelleRH() {
  const [annee, setAnnee] = useState(new Date().getFullYear());
  const [previewRows, setPreviewRows] = useState([]);
  const [loadingPreview, setLoadingPreview] = useState(false);
  const [executing, setExecuting] = useState(false);
  const [isClosed, setIsClosed] = useState(false);

  const [tabKey, setTabKey] = useState("simulation");
  const [anneesHist, setAnneesHist] = useState([]);
  const [anneeHistSelected, setAnneeHistSelected] = useState(null);
  const [histRows, setHistRows] = useState([]);
  const [loadingHist, setLoadingHist] = useState(false);
  const [onlyLosses, setOnlyLosses] = useState(false);


  const extractRows = (res) => {
    if (Array.isArray(res?.data?.rows)) return res.data.rows;
    if (Array.isArray(res?.data?.data)) return res.data.data; // compat legacy
    if (Array.isArray(res?.data)) return res.data;            // fallback
    return [];
  };

  const [filters, setFilters] = useState({
    direction: "",
    service: "",
    fonction: "",
    search: ""
  });
  
    const normalize = (v) => (v || "").toString().toLowerCase().trim();

    const applyFilters = (rows) => {
      return rows.filter(r => {

        // 🔸 Filtre Direction
        if (filters.direction) {
          const rowDirection = (r.direction || r.direction_nom || "").toString();
          if (rowDirection !== filters.direction) {
            return false;
          }
        }

        // 🔸 Filtre pertes uniquement
        if (onlyLosses && Number(r.perte) <= 0) {
          return false;
        }

        // 🔸 Recherche texte
        if (filters.search) {
          const s = normalize(filters.search);
          const haystack = `
            ${r.matricule || ""}
            ${r.nom || ""}
            ${r.prenom || ""}
            ${r.personnel || ""}
          `.toLowerCase();

          if (!haystack.includes(s)) return false;
        }

        return true;
      });
    };

    const filteredPreviewRows = useMemo(
      () => applyFilters(previewRows),
      [previewRows, filters]
    );

    const filteredHistRows = useMemo(
      () => applyFilters(histRows),
      [histRows, filters]
    );
    const synthese = useMemo(() => {
      const pertes = filteredPreviewRows.filter(r => Number(r.perte) > 0);
      return {
        nbAgents: pertes.length,
        joursPerdus: pertes.reduce((s, r) => s + Number(r.perte), 0)
      };
    }, [filteredPreviewRows]);

    const directions = useMemo(() => {
    const rows = tabKey === "simulation" ? previewRows : histRows;
      return Array.from(
        new Set(
          rows
            .map(r => r.direction || r.direction_nom)
            .filter(Boolean)
        )
      ).sort();
    }, [previewRows, histRows, tabKey]);

  /* ===============================
     STATUS + PREVIEW
  =============================== */
  const fetchStatusAndPreview = async () => {
    try {
      setLoadingPreview(true);

      const statusRes = await api.get(`/api/rh/cloture/status/${annee}`);
      const closed = Boolean(statusRes.data?.closed);
      setIsClosed(closed);

      const url = closed
        ? `/api/rh/cloture/closed/${annee}`
        : `/api/rh/cloture/preview/${annee}`;

      const res = await api.get(url);
      setPreviewRows(extractRows(res));

    } catch {
      toast.error("Erreur lors du chargement des données");
      setPreviewRows([]);
      setIsClosed(false);
    } finally {
      setLoadingPreview(false);
    }
  };

  /* ===============================
     EXECUTE CLOTURE
  =============================== */
  const executeCloture = async () => {
    if (!window.confirm(
      `⚠️ CONFIRMATION RH\n\nCette action est IRRÉVERSIBLE.\nClôturer définitivement l'année ${annee} ?`
    )) return;

    try {
      setExecuting(true);
      await api.post(`/api/rh/cloture/execute/${annee}`);
      toast.success(`Clôture annuelle ${annee} effectuée`);
      fetchStatusAndPreview();
      fetchHistorique();
    } catch (e) {
      toast.error(e.response?.data?.message || "Erreur de clôture");
    } finally {
      setExecuting(false);
    }
  };

  /* ===============================
     HISTORIQUE ANNEES
  =============================== */
  const fetchHistorique = async () => {
    try {
      const res = await api.get("/api/rh/cloture/historique");
      const data = Array.isArray(res.data) ? res.data : [];
      setAnneesHist(data);
      if (data.length > 0 && !anneeHistSelected) setAnneeHistSelected(data[0]);
    } catch {
      setAnneesHist([]);
      setAnneeHistSelected(null);
    }
  };

  /* ===============================
     HISTORIQUE ROWS
  =============================== */
  useEffect(() => {
    if (!anneeHistSelected) return;
    setLoadingHist(true);
    api.get(`/api/rh/cloture/closed/${anneeHistSelected}`)
      .then(res => {
        const rows = extractRows(res).map(r => ({
          ...r,
          validated_at_local: r.validated_at
            ? new Date(r.validated_at).toLocaleString("fr-FR", {
                timeZone: "Indian/Antananarivo"
              })
            : null
        }));
        setHistRows(rows);
      })
      .finally(() => setLoadingHist(false));
  }, [anneeHistSelected]);

  useEffect(() => {
    fetchStatusAndPreview();
    fetchHistorique();
  }, [annee]);

  return (
    <NavigationLayout>
      <h4 className="mb-3">
        🗓️ Clôture annuelle des congés (RH)
        <Badge bg={isClosed ? "danger" : "success"} className="ms-2">
          {isClosed ? "ANNÉE CLÔTURÉE" : "SIMULATION RH"}
        </Badge>
      </h4>

      <div className="mb-3" style={{ maxWidth: 200 }}>
        <label className="form-label">Année</label>
        <input
          type="number"
          min="2000"
          max="2100"
          value={annee}
          disabled={executing || loadingPreview}
          className="form-control"
          onChange={(e) => setAnnee(Number(e.target.value))}
        />
      </div>
      <Alert variant="light" className="border mb-3">
        <div className="row g-2">          
          <div className="col-md-3">
            <Form.Control
              placeholder="🔎 Matricule / Nom / Prénom"
              value={filters.search}
              onChange={(e) => setFilters(f => ({
                ...f,
                search: e.target.value
              }))}
            />
            <Form.Check
              type="switch"
              label="Afficher uniquement les pertes"
              checked={onlyLosses}
              onChange={() => setOnlyLosses(v => !v)}
            />
          </div>
          <div className="col-md-3">
            <Form.Select
              value={filters.direction}
              onChange={(e) =>
                setFilters(f => ({ ...f, direction: e.target.value }))
              }
            >
              <option value="">🏢 Toutes les directions</option>
              {directions.map(dir => (
                <option key={dir} value={dir}>
                  {dir}
                </option>
              ))}
            </Form.Select>
          </div>
          <div className="col-md-3">
              <Button
                size="sm"
                variant="outline-secondary"
                onClick={() => setFilters({
                direction: "", search: "" 
                })}
              >
                🔄 Réinitialiser filtres
              </Button>
          </div>
        </div>
      </Alert>

      <Tabs activeKey={tabKey} onSelect={(k) => setTabKey(k)} className="mb-3">
        {/* =============================== SIMULATION =============================== */}
        <Tab eventKey="simulation" title="Simulation">
          {isClosed ? (
            <Alert variant="danger">🔒 L’année {annee} est déjà clôturée.</Alert>
          ) : loadingPreview ? (
            <div className="text-center my-4"><Spinner animation="border" /></div>
          ) : previewRows.length === 0 ? (
            <Alert variant="info">Aucune donnée à clôturer pour l’année {annee}.</Alert>
          ) : (
            <>
              { synthese.nbAgents > 0 && (
                <Alert variant="warning" className="fw-semibold">
                  ⚠️ <strong>{synthese.nbAgents}</strong> agents perdent des congés —
                  <strong className="ms-1 text-danger">
                    {synthese.joursPerdus.toFixed(2)} jours perdus
                  </strong>
                </Alert>
              )}
              <div className="d-flex gap-2 mb-3">
                <Button variant="success" onClick={() => window.open(`/api/rh/cloture/preview/export/excel/${annee}`)}>
                  📊 Export simulation
                </Button>
              </div>

              <Button
                variant="danger"
                onClick={executeCloture}
                disabled={executing || isClosed}
              >
                {executing ? "⏳ Clôture en cours..." : `🔒 Clôturer définitivement l’année ${annee}`}
              </Button>
              <ClotureTable
                rows={filteredPreviewRows}
                annee={annee}
              />              
            </>
          )}
        </Tab>

        {/* =============================== HISTORIQUE =============================== */}
        <Tab eventKey="historique" title="Historique">
          {anneesHist.length === 0 ? (
            <Alert variant="info">Aucune année clôturée enregistrée.</Alert>
          ) : (
            <>
              <Form.Group className="mb-3" style={{ maxWidth: 200 }}>
                <Form.Label>Année</Form.Label>
                <Form.Select
                  value={anneeHistSelected || ""}
                  onChange={(e) => setAnneeHistSelected(Number(e.target.value))}
                  disabled={loadingHist}
                >
                  {anneesHist.map((y) => (
                    <option key={y} value={y}>{y}</option>
                  ))}
                </Form.Select>
              </Form.Group>

              {loadingHist ? (
                <div className="text-center my-4"><Spinner animation="border" /></div>
              ) : histRows.length === 0 ? (
                <Alert variant="info">Aucune donnée pour l’année {anneeHistSelected}.</Alert>
              ) : (
                <>
                  <div className="d-flex gap-2 mb-3">
                    <Button variant="secondary" onClick={() => window.open(`/api/rh/cloture/export/excel/${anneeHistSelected}`)}>
                      📊 Export Excel RH
                    </Button>
                    <Button variant="secondary" onClick={() => window.open(`/api/rh/cloture/export/pdf/${anneeHistSelected}`)}>
                      📄 Export PDF signé RH
                    </Button>
                  </div>
                  <ClotureTable
                    rows={filteredHistRows}
                    annee={anneeHistSelected}
                    showValidation
                  />                  
                </>
              )}
            </>
          )}
        </Tab>
      </Tabs>
    </NavigationLayout>
  );
}
