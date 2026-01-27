import React, { useEffect, useState } from "react";
import { Button, Card, Col, Form, InputGroup, Row, Alert } from "react-bootstrap";
import AsyncSelect from "react-select/async";
import axios from "axios";

export default function CongeForm({
  setReloadLeaves,
  editingId,
  form,
  setForm,
  clearEditing,
  notifySuccess,
  setSelectedPersonnelId,
}) {
  const [joursUtilises, setJoursUtilises] = useState(0);
  const [locked, setLocked] = useState(false);
  const [error, setError] = useState(null);

  const isEditing = Boolean(editingId);

  const [selectedPersonnel, setSelectedPersonnel] = useState(null);
  const [selectedLeaveType, setSelectedLeaveType] = useState(null);
  

  /* ================= CALCUL DES JOURS ================= */
  const calculateJours = (dateDebut, dateFin, heureDebut, heureFin) => {
    if (!dateDebut || !dateFin || !heureDebut || !heureFin) return 0;

    const startDate = new Date(dateDebut);
    const endDate = new Date(dateFin);

    if (endDate < startDate) return 0;

    // 🔹 CAS 1 : CONGÉ SUR UNE SEULE JOURNÉE → calcul horaire
    if (dateDebut === dateFin) {
      const start = new Date(`${dateDebut}T${heureDebut}`);
      const end = new Date(`${dateFin}T${heureFin}`);
      if (end <= start) return 0;

      let hours = (end - start) / 3600000;

      // Pause déjeuner 12h00–13h30
      const pauseStart = new Date(`${dateDebut}T12:00`);
      const pauseEnd = new Date(`${dateDebut}T13:30`);
      if (start < pauseEnd && end > pauseStart) {
        hours -= (Math.min(end, pauseEnd) - Math.max(start, pauseStart)) / 3600000;
      }

      return Math.round((hours / 8) * 100) / 100;
    }

    // 🔹 CAS 2 : CONGÉ MULTI-JOURS → 1 JOUR = 1
    const diffDays =
      Math.floor((endDate - startDate) / 86400000) + 1;

    return diffDays;
  };


  useEffect(() => {
    setJoursUtilises(
      calculateJours(
        form.date_debut,
        form.date_fin,
        form.heure_debut,
        form.heure_fin
      )
    );
  }, [form.date_debut, form.date_fin, form.heure_debut, form.heure_fin]);

  /* ================= LOCK CHECK ================= */
  useEffect(() => {
    setLocked(form.status === "approuve_rh" || form.status === "rejete");
  }, [form.status]);

  /* ================= VALIDATION RH ================= */
  const validateForm = () => {
    if (!form.personnel_id) return "Le personnel est obligatoire.";
    if (!form.leave_type_id) return "Le type de congé est obligatoire.";
    if (!form.date_debut || !form.date_fin)
      return "Les dates de début et fin sont obligatoires.";
    if (joursUtilises <= 0)
      return "La durée du congé est invalide (0 jour calculé).";
    return null;
  };

  /* ================= ASYNC LOADERS ================= */
  const loadPersonnels = async (q) => {
    const res = await axios.get(`/api/personnels-search?q=${q || ""}`);
    return res.data.map((p) => ({
      value: p.id,
      label: `${p.matricule} - ${p.nom} ${p.prenom}`,
    }));
  };

  const loadLeaveTypes = async (q) => {
    const res = await axios.get(`/api/leave-types-search?q=${q || ""}`);
    return res.data.map((t) => ({ value: t.id, label: t.nom }));
  };

  /* ================= SUBMIT ================= */
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (locked) return;

    const validationError = validateForm();
    if (validationError) {
      setError(validationError);
      return;
    }

    const payload = { ...form, jours_utilises: joursUtilises };

    try {
      if (editingId) {
        await axios.put(`/api/rh/leaves/${editingId}`, payload);
        notifySuccess("✔ Congé mis à jour");
      } else {
        await axios.post(`/api/rh/leaves`, payload);
        notifySuccess("✔ Congé ajouté");
      }

      setReloadLeaves((r) => r + 1);
      handleReset();
      setError(null);
    } catch (error) {
      setError(
        error.response?.data?.message ||
          "Erreur lors de l'enregistrement du congé"
      );
    }
  };

  /* ================= RESET ================= */
  const handleReset = () => {
    clearEditing();
    setSelectedPersonnel(null);   // ✅ vide AsyncSelect personnel
    setSelectedLeaveType(null);   // ✅ vide AsyncSelect type
    setJoursUtilises(0);
    setError(null);
    setForm({
      personnel_id: "",
      personnel_label: "",
      leave_type_id: "",
      leave_type_label: "",
      date_debut: "",
      date_fin: "",
      heure_debut: "08:00",
      heure_fin: "17:30",
      raison: "",
      status: "",
    });

  };

  return (
    <Card className="p-4 mb-4 shadow-sm border-0">
      <h5>{isEditing ? "✏ Modifier un congé" : "➕ Ajouter un congé"}</h5>

      {locked && (
        <Alert variant="warning">
          ⚠ Ce congé est verrouillé après validation RH ou rejet.
        </Alert>
      )}

      {joursUtilises === 0 &&
        form.date_debut &&
        form.date_fin && (
          <Alert variant="danger">
            ⚠ La durée calculée est nulle. Vérifiez les dates/heures.
          </Alert>
        )}

      <Alert variant="info">
        ℹ️ Journée de travail : <strong>8h</strong> — Pause{" "}
        <strong>12h00–13h30</strong> — Week-ends inclus
      </Alert>

      {error && <Alert variant="danger">{error}</Alert>}

      <Form onSubmit={handleSubmit}>
        <Row className="g-3">
          <Col md={6}>
            <Form.Label>Personnel</Form.Label>
            {isEditing ? (
              <Form.Control value={form.personnel_label || ""} disabled />
            ) : (
              <AsyncSelect
                cacheOptions
                defaultOptions
                loadOptions={loadPersonnels}
                value={selectedPersonnel}
                onChange={(s) => {
                  setSelectedPersonnel(s);
                  setForm({ ...form, personnel_id: s?.value || "" });
                  setSelectedPersonnelId?.(s?.value || "");
                }}
                isClearable
                isDisabled={locked}
              />
            )}
          </Col>

          <Col md={6}>
            <Form.Label>Type de congé</Form.Label>
            {isEditing ? (
              <Form.Control value={form.leave_type_label || ""} disabled />
            ) : (
              <AsyncSelect
                cacheOptions
                defaultOptions
                loadOptions={loadLeaveTypes}
                value={selectedLeaveType}
                onChange={(s) => {
                  setSelectedLeaveType(s)
                  setForm({ ...form, leave_type_id: s?.value || "" })
                }}
                isClearable
                isDisabled={locked}
              />
            )}
          </Col>

          <Col md={6}>
            <Form.Label>Date & Heure début</Form.Label>
            <InputGroup>
              <Form.Control
                type="date"
                value={form.date_debut}
                onChange={(e) =>
                  setForm({ ...form, date_debut: e.target.value })
                }
                disabled={locked}
              />
              <Form.Control
                type="time"
                value={form.heure_debut}
                onChange={(e) =>
                  setForm({ ...form, heure_debut: e.target.value })
                }
                disabled={locked}
              />
            </InputGroup>
          </Col>

          <Col md={6}>
            <Form.Label>Date & Heure fin</Form.Label>
            <InputGroup>
              <Form.Control
                type="date"
                value={form.date_fin}
                onChange={(e) =>
                  setForm({ ...form, date_fin: e.target.value })
                }
                disabled={locked}
              />
              <Form.Control
                type="time"
                value={form.heure_fin}
                onChange={(e) =>
                  setForm({ ...form, heure_fin: e.target.value })
                }
                disabled={locked}
              />
            </InputGroup>
          </Col>

          <Col md={12}>
            <Form.Label>Raison</Form.Label>
            <Form.Control
              as="textarea"
              rows={3}
              value={form.raison}
              onChange={(e) =>
                setForm({ ...form, raison: e.target.value })
              }
              disabled={locked}
            />
          </Col>

          <Col md={4}>
            <Form.Label>Jours utilisés</Form.Label>
            <Form.Control value={joursUtilises} disabled />
          </Col>
          <small className="text-muted">
            ⚠️ Les jours fériés sont automatiquement exclus après validation.
          </small>


          <Col md={12} className="d-flex gap-2">
            <Button type="submit" className="w-100" disabled={locked}>
              {isEditing ? "Mettre à jour" : "Ajouter"}
            </Button>
            <Button
              variant="outline-secondary"
              className="w-100"
              onClick={handleReset}
              disabled={locked}
            >
              🔄 Réinitialiser
            </Button>
          </Col>
        </Row>
      </Form>
    </Card>
  );
}
