// resources/js/components/GestionPersonnelsModern.jsx
import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  Row,
  Col,
  Card,
  InputGroup,
  Form,
  Button,
  Table,
  Badge,
  Spinner,
  Modal,
  Image,
  ProgressBar,
  Pagination,
  Overlay,
  Tooltip,
} from "react-bootstrap";
import { FaSearch, FaSyncAlt, FaFileExport, FaPlus, FaEdit, FaTrash } from "react-icons/fa";
import axios from "../axios";
import Select from "react-select";
import { toast, ToastContainer } from "react-toastify";
import ImportLegacyData from "./ImportLegacyData";
import NavigationLayout from "../components/NavigationLayout";
import * as XLSX from "xlsx";
import "react-toastify/dist/ReactToastify.css";
import { useNavigate } from "react-router-dom";

/**
 * GestionPersonnelsModern.jsx
 * Version Ny Havana — modernisée et ergonomique
 *
 * Dépendances :
 * - react-bootstrap
 * - react-select
 * - react-toastify
 * - axios (pré-configuré dans ../axios)
 *
 * Points d'attention :
 * - Le logo est attendu à /images/ny-havana-logo.png (adapter si besoin)
 * - Endpoints : GET /api/personnels (page/per_page/q/direction_id/service_id),
 *   POST /api/personnels, POST /api/personnels/{id}?_method=PUT, DELETE /api/personnels/{id}
 * - ImportPersonnels et ImportLegacyData doivent exposer props onImportStart/onImportProgress/onImportFinish
 */

/* ---------- constantes ---------- */
const DEBOUNCE_MS = 350;
const NY_PRIMARY = "#B30000";
const PLACEHOLDER_AVATAR = "/images/avatar-placeholder.png";

/* ---------- composant ---------- */
export default function GestionPersonnelsModern() {
  // data
  const [personnels, setPersonnels] = useState([]);
  const [directions, setDirections] = useState([]);
  const [services, setServices] = useState([]);
  const [fonctions, setFonctions] = useState([]);

  // UI / control
  const [loading, setLoading] = useState(false);
  const [importing, setImporting] = useState(false);
  const [importProgress, setImportProgress] = useState(0);
  const [q, setQ] = useState("");
  const [debouncedQ, setDebouncedQ] = useState("");
  const [filterDirection, setFilterDirection] = useState(null);
  const [filterService, setFilterService] = useState(null);

  const [currentPage, setCurrentPage] = useState(1);
  const [perPage] = useState(12);
  const [totalPages, setTotalPages] = useState(1);

  // selection / batch
  const [selectedIds, setSelectedIds] = useState([]);

  // modal form
  const [showModal, setShowModal] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    nom: "",
    prenom: "",
    matricule: "",
    date_naissance: "",
    date_entree: "",
    adresse: "",
    cin: "",
    diplome: "",
    direction_id: "",
    service_id: "",
    fonction_id: "",
    photo: null,
  });
  const [photoPreview, setPhotoPreview] = useState(null);
  const [saving, setSaving] = useState(false);

  const navigate = useNavigate();

  // sort client-side (current page)
  const [sortConfig, setSortConfig] = useState({ key: "nom", direction: "asc" });

  // theme
  const [darkMode, setDarkMode] = useState(false);

  // mobile overlay for potential menu (exposed)
  const [showOverlay, setShowOverlay] = useState(false);

  /* ---------- helper toasts ---------- */
  const toastError = (m) => toast.error(m);
  const toastSuccess = (m) => toast.success(m);

  /* ---------- fetch structures ---------- */
  const fetchStructures = useCallback(async () => {
    try {
      const [d, s, f] = await Promise.all([
        axios.get("/api/directions"),
        axios.get("/api/services"),
        axios.get("/api/fonctions"),
      ]);
      setDirections(d.data || []);
      setServices(s.data || []);
      setFonctions(f.data || []);
    } catch (e) {
      console.error(e);
      toastError("Impossible de charger les structures");
    }
  }, []);

  /* ---------- fetch personnels (server-side pagination + search) ---------- */
  const fetchPersonnels = useCallback(
    async (page = 1) => {
      setLoading(true);
      try {
        const params = { page, per_page: perPage };
        if (debouncedQ) params.q = debouncedQ;
        if (filterDirection) params.direction_id = filterDirection.value;
        if (filterService) params.service_id = filterService.value;
        const res = await axios.get("/api/personnels", { params });
        // backend shape: { data: [...], last_page, current_page }
        const payload = res.data;
        setPersonnels(payload.data || payload); // tolerate different shapes
        setTotalPages(payload.last_page || 1);
        setCurrentPage(payload.current_page || page);
      } catch (e) {
        console.error(e);
        toastError("Erreur récupération personnels");
      } finally {
        setLoading(false);
      }
    },
    [debouncedQ, filterDirection, filterService, perPage]
  );

  useEffect(() => {
    fetchStructures();
  }, [fetchStructures]);

  // debounce q
  useEffect(() => {
    const t = setTimeout(() => setDebouncedQ(q.trim()), DEBOUNCE_MS);
    return () => clearTimeout(t);
  }, [q]);

  // reload when filters/pagination change
  useEffect(() => {
    fetchPersonnels(currentPage);
  }, [fetchPersonnels, currentPage]);

  /* ---------- sorting client-side ---------- */
  const sortedPersonnels = useMemo(() => {
    const arr = Array.isArray(personnels) ? [...personnels] : [];
    if (!sortConfig.key) return arr;
    arr.sort((a, b) => {
      const A = (a[sortConfig.key] ?? "").toString().toLowerCase();
      const B = (b[sortConfig.key] ?? "").toString().toLowerCase();
      if (A < B) return sortConfig.direction === "asc" ? -1 : 1;
      if (A > B) return sortConfig.direction === "asc" ? 1 : -1;
      return 0;
    });
    return arr;
  }, [personnels, sortConfig]);

  const requestSort = (key) =>
    setSortConfig((prev) => (prev.key === key ? { key, direction: prev.direction === "asc" ? "desc" : "asc" } : { key, direction: "asc" }));

  /* ---------- selection helpers ---------- */
  const toggleSelect = (id) => setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  const toggleSelectAll = () => {
    if (selectedIds.length === sortedPersonnels.length) setSelectedIds([]);
    else setSelectedIds(sortedPersonnels.map((p) => p.id));
  };

  /* ---------- export excel ---------- */
  const mapForExport = (p) => ({
    Matricule: p.matricule || "",
    Nom: p.nom || "",
    Prenom: p.prenom || "",
    Direction: p.direction?.nom || "",
    Service: p.service?.nom || "",
    Fonction: p.fonction?.nom || "",
    Date_naissance: p.date_naissance || "",
    Date_entree: p.date_entree || "",
    Adresse: p.adresse || "",
  });
  const downloadXLS = (rows, filename) => {
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Personnels");
    XLSX.writeFile(wb, filename);
    toastSuccess("Export généré");
  };
  const exportExcel = async (all = false) => {
    try {
      if (all) {
        const res = await axios.get("/api/personnels", { params: { page: 1, per_page: 10000 } });
        const rows = (res.data.data || res.data).map(mapForExport);
        downloadXLS(rows, "personnels_all.xlsx");
      } else {
        const rows = (personnels || []).map(mapForExport);
        downloadXLS(rows, `personnels_page_${currentPage}.xlsx`);
      }
    } catch (e) {
      console.error(e);
      toastError("Erreur export");
    }
  };

  /* ---------- import callbacks (used by child import components) ---------- */
  const onImportStart = () => {
    setImporting(true);
    setImportProgress(8);
    setShowOverlay(true);
  };
  const onImportProgress = (pct) => setImportProgress(pct);
  const onImportFinish = () => {
    setImporting(false);
    setImportProgress(100);
    setTimeout(() => setImportProgress(0), 600);
    setShowOverlay(false);
    fetchPersonnels(1);
    toastSuccess("Import terminé");
  };

  /* ---------- CRUD : open modal / save / delete ---------- */
  const openNew = () => {
    setEditing(null);
    setForm({
      nom: "",
      prenom: "",
      matricule: "",
      date_naissance: "",
      date_entree: "",
      adresse: "",
      cin: "",
      diplome: "",
      direction_id: "",
      service_id: "",
      fonction_id: "",
      photo: null,
    });
    setPhotoPreview(null);
    setShowModal(true);
  };
  const openEdit = (p) => {
    setEditing(p.id);
    setForm({
      nom: p.nom || "",
      prenom: p.prenom || "",
      matricule: p.matricule || "",
      date_naissance: p.date_naissance || "",
      date_entree: p.date_entree || "",
      adresse: p.adresse || "",
      cin: p.cin || "",
      diplome: p.diplome || "",
      direction_id: p.direction_id || "",
      service_id: p.service_id || "",
      fonction_id: p.fonction_id || "",
      photo: null,
    });
    setPhotoPreview(p.photo_url || null);
    setShowModal(true);
  };

  const handlePhotoChange = (file) => {
    setForm((s) => ({ ...s, photo: file }));
    if (file) setPhotoPreview(URL.createObjectURL(file));
    else setPhotoPreview(null);
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const fd = new FormData();
      Object.keys(form).forEach((k) => {
        if (form[k] !== null && form[k] !== undefined) fd.append(k, form[k]);
      });
      if (editing) {
        await axios.post(`/api/personnels/${editing}?_method=PUT`, fd, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        toastSuccess("Personnel mis à jour");
      } else {
        await axios.post("/api/personnels", fd, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        toastSuccess("Personnel ajouté");
      }
      setShowModal(false);
      fetchPersonnels(1);
    } catch (e) {
      console.error(e);
      if (e.response?.status === 422) {
        const msgs = Object.values(e.response.data.errors).flat().join("\n");
        toastError("Validation :\n" + msgs);
      } else {
        toastError("Erreur sauvegarde");
      }
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    if (!confirm("Confirmer la suppression ?")) return;
    try {
      await axios.delete(`/api/personnels/${id}`);
      toastSuccess("Supprimé");
      fetchPersonnels(currentPage);
    } catch (e) {
      console.error(e);
      toastError("Erreur suppression");
    }
  };

  const handleDeleteSelected = async () => {
    if (selectedIds.length === 0) return;
    if (!confirm(`Supprimer ${selectedIds.length} personnels ?`)) return;
    try {
      await axios.post("/api/personnels/delete-multiple", { ids: selectedIds });
      setSelectedIds([]);
      toastSuccess("Suppression groupée effectuée");
      fetchPersonnels(currentPage);
    } catch (e) {
      console.error(e);
      toastError("Erreur suppression groupée");
    }
  };

  /* ---------- options helpers ---------- */
  const directionOptions = useMemo(() => directions.map((d) => ({ label: d.nom, value: d.id })), [directions]);
  const serviceOptions = useMemo(() => services.map((s) => ({ label: s.nom, value: s.id, direction_id: s.direction_id })), [services]);
  const fonctionOptions = useMemo(() => fonctions.map((f) => ({ label: f.nom, value: f.id, service_id: f.service_id })), [fonctions]);

  /* ---------- small UI helpers ---------- */
  const getSortArrow = (k) => (sortConfig.key === k ? (sortConfig.direction === "asc" ? " ▲" : " ▼") : "");

  /* ---------- render ---------- */
  return (
    <NavigationLayout>
      <div className={`gp-root ${darkMode ? "gp-dark" : ""}`}>

        {/* Header */}
        <Card className="gp-header mb-3">
          <Card.Body className="d-flex align-items-center justify-content-between">
            <div className="d-flex align-items-center gap-3">
              <Image src="/images/ny-havana-logo.png" rounded style={{ width: 56, height: 56, objectFit: "cover" }} />
              <div>
                <h5 className="mb-0" style={{ color: NY_PRIMARY }}>Ny Havana</h5>
                <small className="text-muted">Gestion des personnels / RH</small>
              </div>
            </div>

            <div className="d-flex align-items-center gap-2">
              <Button variant="outline-secondary" size="sm" onClick={() => { setQ(""); setFilterDirection(null); setFilterService(null); fetchPersonnels(1); }}>
                <FaSyncAlt /> Rafraîchir
              </Button>

              <Button variant={darkMode ? "light" : "dark"} size="sm" onClick={() => setDarkMode((s) => !s)}>
                {darkMode ? "🌙 Sombre" : "🌞 Clair"}
              </Button>
            </div>
          </Card.Body>
        </Card>

        {/* Toolbar */}
        <Row className="mb-3">
          <Col lg={8}>
            <Card className="p-2">
              <Row className="g-2 align-items-center">
                <Col md={6}>
                  <InputGroup>
                    <InputGroup.Text><FaSearch /></InputGroup.Text>
                    <Form.Control placeholder="Rechercher nom / matricule..." value={q} onChange={(e) => { setQ(e.target.value); setCurrentPage(1); }} />
                    <Button variant="outline-primary" onClick={() => fetchPersonnels(1)}>Rechercher</Button>
                  </InputGroup>
                  <small className="text-muted">Recherche (recherche côté serveur après pause)</small>
                </Col>

                <Col md={3}>
                  <Select options={directionOptions} value={filterDirection} onChange={(v) => { setFilterDirection(v); setFilterService(null); setCurrentPage(1); }} isClearable placeholder="Filtrer par direction" />
                </Col>

                <Col md={3}>
                  <Select options={serviceOptions.filter(s => !filterDirection || s.direction_id === filterDirection.value)} value={filterService} onChange={(v) => { setFilterService(v); setCurrentPage(1); }} isClearable isDisabled={!filterDirection} placeholder="Filtrer par service" />
                </Col>
              </Row>
            </Card>
          </Col>

          <Col lg={4}>
            <Card className="p-2 h-100 d-flex align-items-center justify-content-between">
              <div className="d-flex gap-2 align-items-center">
                <Button variant="primary" onClick={openNew}><FaPlus /> Ajouter</Button>
                <Button variant="success" onClick={() => exportExcel(false)}><FaFileExport /> Export page</Button>
                <Button variant="outline-success" onClick={() => exportExcel(true)}>Export tout</Button>
              </div>

              <div className="d-flex gap-2 align-items-center">                
                <div className="d-flex gap-1">
                  <ImportLegacyData onImportStart={onImportStart} onImportProgress={onImportProgress} onImportFinish={onImportFinish} />
                </div>
              </div>
            </Card>
          </Col>
        </Row>

        {/* Import progress */}
        {importing && (
          <Row className="mb-3">
            <Col>
              <ProgressBar now={importProgress} label={`${importProgress}%`} animated striped style={{ height: 20 }} />
            </Col>
          </Row>
        )}

        {/* Table + batch actions */}
        <Card className="mb-3">
          <Card.Body className="p-0">
            <div className="table-toolbar d-flex align-items-center justify-content-between p-2 border-bottom">
              <div>
                <Button variant="danger" size="sm" disabled={selectedIds.length === 0} onClick={handleDeleteSelected}>Supprimer la sélection ({selectedIds.length})</Button>
              </div>
              <div className="text-muted">Résultats : <strong>{(personnels || []).length}</strong></div>
            </div>

            <div className="table-responsive">
              <Table hover bordered className="mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: 40 }}>
                      <input type="checkbox" onChange={toggleSelectAll} checked={selectedIds.length > 0 && selectedIds.length === sortedPersonnels.length && sortedPersonnels.length > 0} />
                    </th>
                    <th style={{ cursor: "pointer" }} onClick={() => requestSort("nom")}>Nom {getSortArrow("nom")}</th>
                    <th style={{ cursor: "pointer" }} onClick={() => requestSort("prenom")}>Prénom {getSortArrow("prenom")}</th>
                    <th style={{ cursor: "pointer" }} onClick={() => requestSort("matricule")}>Matricule {getSortArrow("matricule")}</th>
                    <th>Direction</th>
                    <th>Service</th>
                    <th>Fonction</th>
                    <th style={{ width: 180 }}>Actions</th>
                  </tr>
                </thead>

                <tbody>
                  {loading ? (
                    <tr><td colSpan={8} className="text-center py-5"><Spinner animation="border" /></td></tr>
                  ) : sortedPersonnels.length === 0 ? (
                    <tr><td colSpan={8} className="text-center py-4 text-muted">Aucun personnel trouvé</td></tr>
                  ) : sortedPersonnels.map((p) => (
                    <tr key={p.id}>
                      <td>
                        <input type="checkbox" checked={selectedIds.includes(p.id)} onChange={() => toggleSelect(p.id)} />
                      </td>

                      <td>
                        <div className="d-flex align-items-center gap-2">
                          <Image src={p.photo_url || PLACEHOLDER_AVATAR} roundedCircle style={{ width: 44, height: 44, objectFit: "cover" }} />
                          <div>
                            <div className="fw-semibold">{p.nom}</div>
                            <small className="text-muted">{p.matricule}</small>
                          </div>
                        </div>
                      </td>

                      <td>{p.prenom}</td>
                      <td>{p.matricule}</td>
                      <td>{p.direction?.nom ? <Badge bg="primary">{p.direction.nom}</Badge> : "-"}</td>
                      <td>{p.service?.nom ? <Badge bg="info">{p.service.nom}</Badge> : "-"}</td>
                      <td>{p.fonction?.nom ? <Badge bg="secondary">{p.fonction.nom}</Badge> : "-"}</td>

                      <td>
                        <div className="d-flex gap-1">
                          <Button size="sm" variant="outline-primary" onClick={() => openEdit(p)}><FaEdit /></Button>
                          <Button size="sm" variant="outline-danger" onClick={() => handleDelete(p.id)}><FaTrash /></Button>
                          <Button
                            size="sm"
                            variant="outline-secondary"
                            onClick={() => navigate(`/personnels/${p.id}`)}
                          >
                            👤 Dossier RH
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            </div>
          </Card.Body>
        </Card>

        {/* Pagination */}
        <div className="d-flex justify-content-center mb-4">
          <Pagination>
            <Pagination.First onClick={() => setCurrentPage(1)} disabled={currentPage === 1} />
            <Pagination.Prev onClick={() => setCurrentPage((p) => Math.max(1, p - 1))} disabled={currentPage === 1} />
            {Array.from({ length: totalPages }).map((_, i) => {
              const n = i + 1;
              if (totalPages > 10 && Math.abs(n - currentPage) > 4 && n !== 1 && n !== totalPages) return null;
              return <Pagination.Item key={n} active={n === currentPage} onClick={() => setCurrentPage(n)}>{n}</Pagination.Item>;
            })}
            <Pagination.Next onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))} disabled={currentPage === totalPages} />
            <Pagination.Last onClick={() => setCurrentPage(totalPages)} disabled={currentPage === totalPages} />
          </Pagination>
        </div>

        {/* Modal add/edit */}
        <Modal show={showModal} onHide={() => setShowModal(false)} size="lg" centered>
          <Modal.Header closeButton>
            <Modal.Title>{editing ? "Modifier le personnel" : "Ajouter un personnel"}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form>
              <Row>
                <Col md={6}><Form.Group className="mb-2"><Form.Label>Nom</Form.Label><Form.Control value={form.nom} onChange={(e) => setForm({ ...form, nom: e.target.value })} /></Form.Group></Col>
                <Col md={6}><Form.Group className="mb-2"><Form.Label>Prénom</Form.Label><Form.Control value={form.prenom} onChange={(e) => setForm({ ...form, prenom: e.target.value })} /></Form.Group></Col>
              </Row>

              <Row>
                <Col md={4}><Form.Group className="mb-2"><Form.Label>Matricule</Form.Label><Form.Control value={form.matricule} onChange={(e) => setForm({ ...form, matricule: e.target.value })} /></Form.Group></Col>
                <Col md={4}><Form.Group className="mb-2"><Form.Label>Date naissance</Form.Label><Form.Control type="date" value={form.date_naissance} onChange={(e) => setForm({ ...form, date_naissance: e.target.value })} /></Form.Group></Col>
                <Col md={4}><Form.Group className="mb-2"><Form.Label>Date entrée</Form.Label><Form.Control type="date" value={form.date_entree} onChange={(e) => setForm({ ...form, date_entree: e.target.value })} /></Form.Group></Col>
              </Row>

              <Row>
                <Col md={4}><Form.Group className="mb-2"><Form.Label>Direction</Form.Label><Select options={directionOptions} value={directionOptions.find(d => d.value === form.direction_id) || null} onChange={(v) => setForm({ ...form, direction_id: v?.value || "" })} isClearable /></Form.Group></Col>

                <Col md={4}><Form.Group className="mb-2"><Form.Label>Service</Form.Label>
                  <Select options={serviceOptions.filter(s => !form.direction_id || s.direction_id === form.direction_id)} value={serviceOptions.find(s => s.value === form.service_id) || null} onChange={(v) => setForm({ ...form, service_id: v?.value || "" })} isClearable isDisabled={!form.direction_id} />
                </Form.Group></Col>

                <Col md={4}><Form.Group className="mb-2"><Form.Label>Fonction</Form.Label>
                  <Select options={fonctionOptions.filter(f => !form.service_id || f.service_id === form.service_id)} value={fonctionOptions.find(f => f.value === form.fonction_id) || null} onChange={(v) => setForm({ ...form, fonction_id: v?.value || "" })} isClearable isDisabled={!form.service_id} />
                </Form.Group></Col>
              </Row>

              <Row className="align-items-center">
                <Col md={8}><Form.Group className="mb-2"><Form.Label>Adresse</Form.Label><Form.Control value={form.adresse} onChange={(e) => setForm({ ...form, adresse: e.target.value })} /></Form.Group></Col>

                <Col md={4}>
                  <Form.Group className="mb-2"><Form.Label>Photo</Form.Label><Form.Control type="file" accept="image/*" onChange={(e) => handlePhotoChange(e.target.files[0])} /></Form.Group>
                  {photoPreview && <Image src={photoPreview} rounded style={{ width: 84, height: 84, objectFit: "cover", marginTop: 6 }} />}
                </Col>
              </Row>
            </Form>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowModal(false)} disabled={saving}>Annuler</Button>
            <Button variant="primary" onClick={handleSave} disabled={saving}>{saving ? <Spinner animation="border" size="sm" /> : (editing ? "Mettre à jour" : "Enregistrer")}</Button>
          </Modal.Footer>
        </Modal>

        {/* Mobile overlay (when import / menu open) */}
        {showOverlay && <div className="gp-overlay" onClick={() => setShowOverlay(false)} />}

        <ToastContainer position="top-right" autoClose={3000} hideProgressBar />

        {/* Styles (déplacer dans CSS global si tu veux) */}
        <style>{`
          .gp-root { padding: 18px; font-family: Inter, system-ui, Arial; }
          .gp-header { border-radius: 12px; background: linear-gradient(90deg, #fff 0%, #f8f8f8 100%); }
          .gp-dark .gp-header { background: #131313; color: #ddd; }
          .gp-dark { background: #0f0f0f; color: #ddd; }
          .table-toolbar { background: transparent; }
          .gp-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1050; }
          .btn-primary { background: ${NY_PRIMARY}; border-color: ${NY_PRIMARY}; }
          .btn-primary:hover { background: #8f0000; border-color: #8f0000; }
          .badge.bg-danger { background: ${NY_PRIMARY}; border-color: ${NY_PRIMARY}; }
          @media (max-width: 768px) {
            .gp-header .d-flex > div { display: none; } /* compact header on mobile */
          }
        `}</style>
      </div>
    </NavigationLayout>
    
  );
}
