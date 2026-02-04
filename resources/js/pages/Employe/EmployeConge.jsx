import React, { useEffect, useState } from "react";
import api from "@/axios";
import { Table, Button, Spinner, Alert, Card } from "react-bootstrap";
import Pagination from "react-bootstrap/Pagination";
import { ToastContainer, toast } from "react-toastify";
import NavigationLayout from "../../components/NavigationLayout";

const EmployeConge = () => {
  const [conges, setConges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [soldes, setSoldes] = useState([]);
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
  });

  const fetchMesConges = async (page = 1) => {
    setLoading(true);
    try {
      const res = await api.get(`/api/employe/conges?page=${page}`);
      setConges(res.data.data);
      setPagination({
        current_page: res.data.current_page,
        last_page: res.data.last_page,
      });
      setError(null);
    } catch (err) {
      setError(
        err.response?.data?.message ||
        "Impossible de charger l’historique des congés"
      );
    } finally {
      setLoading(false);
    }
  };

  const fetchSoldes = async () => {
    try {
      const res = await api.get("/api/employe/soldes");
      setSoldes(res.data.soldes || []);
    } catch {
      toast.error("Erreur chargement soldes");
    }
  };

  useEffect(() => {
    fetchMesConges();
    fetchSoldes();
  }, []);

  const annulerDemande = async (id) => {
    if (!window.confirm("Annuler cette demande ?")) return;

    try {
      await api.post(`/api/employe/conges/${id}/annuler`);
      toast.success("Demande annulée");
      fetchMesConges();
      fetchSoldes();
    } catch (e) {
      toast.error(e.response?.data?.message || "Erreur");
    }
  };

  const renderStatus = (status) => {
    switch (status) {
      case "approuve_manager":
        return <span className="badge bg-success">Approuvé manager</span>;
      case "approuve_rh":
        return <span className="badge bg-primary">Approuvé RH</span>;
      case "rejete":
        return <span className="badge bg-danger">Rejeté</span>;
      case "annule":
        return <span className="badge bg-secondary">Annulé</span>;
      default:
        return <span className="badge bg-warning text-dark">En attente</span>;
    }
  };

  return (
    <NavigationLayout>
      <div className="container mt-4">
        <ToastContainer />
        

        <h4 className="mb-3">📋 Mes demandes de congé</h4>

        {loading && (
          <div className="text-center my-4">
            <Spinner animation="border" />
          </div>
        )}

        {error && <Alert variant="danger">{error}</Alert>}

        {!loading && conges.length === 0 && (
          <Alert variant="info">Aucune demande de congé trouvée.</Alert>
        )}

        {!loading && conges.length > 0 && (
          <>
            <Table striped bordered hover responsive>
              <thead className="table-light">
                <tr>
                  <th>Type</th>
                  <th>Début</th>
                  <th>Fin</th>
                  <th>Jours</th>
                  <th>Statut</th>
                  <th>Raison</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {conges.map((c) => (
                  <tr key={c.id}>
                    <td>{c.leave_type?.nom}</td>
                    <td>{c.date_debut}</td>
                    <td>{c.date_fin}</td>
                    <td>{Number(c.jours_utilises).toFixed(2)}</td>
                    <td>{renderStatus(c.status)}</td>
                    <td>{c.raison || "-"}</td>
                    <td>
                      {c.status === "en_attente" && (
                        <Button
                          variant="outline-danger"
                          size="sm"
                          onClick={() => annulerDemande(c.id)}
                        >
                          Annuler
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>

            <Pagination className="justify-content-center">
              {[...Array(pagination.last_page)].map((_, i) => (
                <Pagination.Item
                  key={i + 1}
                  active={i + 1 === pagination.current_page}
                  onClick={() => fetchMesConges(i + 1)}
                >
                  {i + 1}
                </Pagination.Item>
              ))}
            </Pagination>
          </>
        )}
        {/* SOLDES */}
        <Card className="mb-4">
          <Card.Header>📊 Soldes de congé</Card.Header>
          <Card.Body>
            <Table size="sm" bordered>
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Droit</th>
                  <th>Utilisés</th>
                  <th>Restant</th>
                </tr>
              </thead>
              <tbody>
                {soldes.length === 0 ? (
                  <tr>
                    <td colSpan="4" className="text-center text-muted">
                      Aucun solde disponible
                    </td>
                  </tr>
                ) : (
                  soldes.map((s) => (
                    <tr key={s.leave_type_id}>
                      <td>{s.leave_type}</td>
                      <td>{Number(s.droit_total).toFixed(2)}</td>
                      <td>{Number(s.jours_utilises).toFixed(2)}</td>
                      <td>
                        <strong
                          className={
                            s.solde_restant < 0
                              ? "text-danger"
                              : "text-success"
                          }
                        >
                          {Number(s.solde_restant).toFixed(2)}
                        </strong>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </Table>
          </Card.Body>
        </Card>
      </div>
    </NavigationLayout>
  );
};

export default EmployeConge;
