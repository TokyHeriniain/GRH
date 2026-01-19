import React from "react";
import { Badge, Button, Card, Table, OverlayTrigger, Tooltip } from "react-bootstrap";
import dayjs from "dayjs";
import "./congeTable.css";

/* ================= UTIL ================= */
const fmt = (v) =>
  v !== null && v !== undefined ? Number(v).toFixed(2) : "--";

/* ================= COMPONENT ================= */
export default function CongeTable({
  leaves = [],
  search = "",
  filter = "all",
  removing = [],
  onDeleteRequest,
  onEdit,
  rhValidate,
  onReject,
  onRejectSelect,
  onViewDetail,
}) {
  const filteredLeaves = Array.isArray(leaves)
    ? leaves.filter((leave) => {
        const fullName =
          `${leave.personnel?.nom || ""} ${leave.personnel?.prenom || ""}`.toLowerCase();
        const matricule = leave.personnel?.matricule?.toLowerCase() || "";

        return (
          (filter === "all" || leave.status === filter) &&
          (fullName.includes(search.toLowerCase()) ||
            matricule.includes(search.toLowerCase()))
        );
      })
    : [];

  return (
    <Card className="conge-card shadow-sm border-0 rounded-4">
      <Card.Body className="p-0">
        <div className="table-responsive">
          <Table hover className="conge-table align-middle mb-0">
            <thead>
              <tr>
                <th>Matricule</th>
                <th>Personnel</th>
                <th>Type</th>
                <th>Période</th>
                <th>Droit</th>
                <th>Utilisés</th>
                <th>Solde</th>
                <th>Statut</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>

            <tbody>
              {filteredLeaves.length === 0 && (
                <tr>
                  <td colSpan={9} className="text-center text-muted py-4">
                    Aucun congé trouvé
                  </td>
                </tr>
              )}

              {filteredLeaves.map((leave) => {
                const isRemoving = removing.includes(leave.id);
                const isEnAttente = leave.status === "en_attente";
                const isLocked =
                  leave.status === "approuve_rh" ||
                  leave.status === "rejete" ||
                  leave.annee_cloturee;

                return (
                  <tr
                    key={leave.id}
                    className={`${isRemoving ? "fade-out" : ""} ${
                      isLocked ? "row-locked" : ""
                    }`}
                  >
                    <td className="text-center fw-semibold">
                      {leave.personnel?.matricule || "—"}
                    </td>

                    <td>
                      <div className="fw-semibold">
                        {leave.personnel?.nom} {leave.personnel?.prenom}
                      </div>
                    </td>

                    <td>{leave.leave_type?.nom || "—"}</td>

                    <td className="small">
                      <div>
                        <strong>Début :</strong>{" "}
                        {dayjs(leave.date_debut).format("DD/MM/YYYY")}{" "}
                        {leave.heure_debut}
                      </div>
                      <div>
                        <strong>Fin :</strong>{" "}
                        {dayjs(leave.date_fin).format("DD/MM/YYYY")}{" "}
                        {leave.heure_fin}
                      </div>
                    </td>

                    <td className="text-center">
                      <Badge bg="info" pill>
                        {fmt(leave.droit_total)} j
                      </Badge>
                    </td>

                    <td className="text-center">
                      <Badge bg="warning" text="dark" pill>
                        −{fmt(leave.jours_utilises)} j
                      </Badge>
                    </td>

                    <td className="text-center">
                      {leave.status === "approuve_rh" ? (
                        <Badge bg="success" pill>
                          {fmt(leave.solde_restant)} j
                        </Badge>
                      ) : (
                        <span className="text-muted">—</span>
                      )}
                    </td>

                    <td className="text-center">
                      <Badge
                        bg={
                          leave.annee_cloturee
                            ? "dark"
                            : leave.status === "approuve_rh"
                            ? "success"
                            : leave.status === "rejete"
                            ? "danger"
                            : "warning"
                        }
                        pill
                      >
                        {leave.annee_cloturee
                          ? "Année clôturée"
                          : leave.status.replace("_", " ")}
                      </Badge>
                    </td>

                    <td className="text-center actions-cell">
                      <OverlayTrigger overlay={<Tooltip>Modifier</Tooltip>}>
                        <span>
                          <Button
                            size="sm"
                            variant="outline-warning"
                            disabled={isLocked}
                            onClick={() => onEdit(leave)}
                          >
                            ✏️
                          </Button>
                        </span>
                      </OverlayTrigger>

                      <OverlayTrigger overlay={<Tooltip>Supprimer</Tooltip>}>
                        <span>
                          <Button
                            size="sm"
                            variant="outline-danger"
                            disabled={isLocked}
                            onClick={() => onDeleteRequest(leave.id)}
                          >
                            🗑️
                          </Button>
                        </span>
                      </OverlayTrigger>

                      <OverlayTrigger overlay={<Tooltip>Valider RH</Tooltip>}>
                        <span>
                          <Button
                            size="sm"
                            variant="outline-success"
                            disabled={!isEnAttente || leave.annee_cloturee}
                            onClick={() => rhValidate(leave.id)}
                          >
                            ✅
                          </Button>
                        </span>
                      </OverlayTrigger>

                      <OverlayTrigger overlay={<Tooltip>Rejeter</Tooltip>}>
                        <span>
                          <Button
                            size="sm"
                            variant="outline-secondary"
                            disabled={!isEnAttente}
                            onClick={() => {
                              onRejectSelect(leave.id);
                              onReject(true);
                            }}
                          >
                            ❌
                          </Button>
                        </span>
                      </OverlayTrigger>
                      <Button
                        size="sm"
                        variant="outline-primary"
                        onClick={() => onViewDetail(leave)}
                      >
                        📄
                      </Button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </Table>
        </div>
      </Card.Body>
    </Card>
  );
}
