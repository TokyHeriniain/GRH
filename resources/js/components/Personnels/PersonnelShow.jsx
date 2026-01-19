import React, { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { Card, Tabs, Tab, Button, Spinner } from "react-bootstrap";
import axios from "@/axios";
import NavigationLayout from "../NavigationLayout";

import PersonnelInfos from "./tabs/PersonnelInfos";
import PersonnelSoldes from "./tabs/PersonnelSoldes";
import HistoriqueCongePersonnel from "./tabs/HistoriqueCongePersonnel";
import PersonnelDocuments from "./tabs/PersonnelDocuments";

export default function PersonnelShow() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [personnel, setPersonnel] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchPersonnel = async () => {
    try {
      const res = await axios.get(`/api/personnels/${id}`);
      setPersonnel(res.data.personnel);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPersonnel();
  }, [id]);

  if (loading) {
    return (
      <NavigationLayout>
        <div className="text-center mt-5">
          <Spinner animation="border" />
        </div>
      </NavigationLayout>
    );
  }

  if (!personnel) {
    return (
      <NavigationLayout>
        <div className="text-center mt-5">
          <p>Personnel introuvable</p>
          <Button onClick={() => navigate(-1)}>Retour</Button>
        </div>
      </NavigationLayout>
    );
  }

  return (
    <NavigationLayout>
      <div className="container mt-4">

        {/* Header fiche */}
        <Card className="mb-3">
          <Card.Body className="d-flex justify-content-between align-items-center">
            <div>
              <h4 className="mb-0">
                {personnel.nom} {personnel.prenom}
              </h4>
              <small className="text-muted">
                Matricule : {personnel.matricule}
              </small>
            </div>

            <Button variant="outline-secondary" onClick={() => navigate(-1)}>
              ← Retour
            </Button>
          </Card.Body>
        </Card>

        {/* Onglets RH */}
        <Tabs defaultActiveKey="infos" className="mb-3">
          <Tab eventKey="infos" title="📄 Informations">
            <PersonnelInfos personnel={personnel} />
          </Tab>

          <Tab eventKey="soldes" title="📊 Soldes congés">
            <PersonnelSoldes personnelId={personnel.id} />
          </Tab>

          <Tab eventKey="conges" title="🗂️ Historique congés">
            <HistoriqueCongePersonnel personnelId={id} />
          </Tab>
          <Tab eventKey="documents" title="🗂️ Gestion des Documents">
            <PersonnelDocuments personnelId={id} />
          </Tab>
        </Tabs>
      </div>
    </NavigationLayout>
  );
}
