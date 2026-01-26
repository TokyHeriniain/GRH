import React, { useEffect, useState } from "react";
import axios from "axios";
import { Row, Col } from "react-bootstrap";
import ComparatifCard from "./ComparatifCard";

export default function ComparatifAnnuel() {
  const [data, setData] = useState(null);

  useEffect(() => {
    axios.get("/api/rh/dashboard/comparatif")
      .then(res => setData(res.data));
  }, []);

  if (!data) return null;

  const d = data.data;

  return (
    <div className="mb-4">
      <h5 className="mb-3">
        📈 Comparaison {data.annee_n} / {data.annee_n1}
      </h5>

      <Row className="g-3">
        <Col md={4}>
          <ComparatifCard
            label="Nombre de congés"
            n={d.conges.n}
            n1={d.conges.n1}
            evolution={d.conges.evolution}
          />
        </Col>

        <Col md={4}>
          <ComparatifCard
            label="Jours consommés"
            n={d.jours.n}
            n1={d.jours.n1}
            evolution={d.jours.evolution}
            unite="j"
          />
        </Col>

        <Col md={4}>
          <ComparatifCard
            label="Jours perdus"
            n={d.pertes.n}
            n1={d.pertes.n1}
            evolution={d.pertes.evolution}
            unite="j"
          />
        </Col>
      </Row>
    </div>
  );
}
