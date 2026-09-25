<?php

namespace App\Http\Controllers;

use App\Models\StudentsEsDubai;
use App\Models\Historiquetrans;
use App\Models\Reclammation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReclamationController extends Controller
{

    // function pour les like et dislike
    public function sendSondages(Request $request)
    {

        $update = Reclammation::where('id', $request->id)->update([
            'message' => $request->comment,
            'avis' => $request->avis,
        ]);

        if ($update) {
            return true;
        } else {
            return false;
        }
    }

    //function pour gerer les retards
    public function delais()
    {
        $rec = Reclammation::where('statut', 'new')->where('created_at', '>', '2023-01-01 00:00:00')->get();
        if ($rec) {
            foreach ($rec as $reclamation) {
                $today = Carbon::parse(now());
                $datecreate = Carbon::parse($reclamation["created_at"]);
                $diff = $today->diff($datecreate)->format('%i') . " minutes";
                if ($diff > 5) {
                    $retard = Reclammation::where('id', $reclamation['id'])->update(["performance" => "Retard"]);
                }
            }
            return response()->json($retard, 200);
        } else {
            return 0;
        }
    }

    //function pour afficher les reclamtion en retard
    public function vueIT()
    {
        $viewreclamRetar = Reclammation::where('performance', 'Retard')->where('statut', 'new')->get();
        return response()->json($viewreclamRetar, 200);
    }



    // public function countReclamations(Request $request)
    // {
    //     $StatusReclamation = Reclammation::where('phoneclient', $request->phoneclient)->get();
    //     if (!empty($StatusReclamation)) {
    //         $CountNewReclam = count(Reclammation::where('phoneclient', $request->phoneclient)->where('statut', 'new')->get());
    //         $CountclosedReclam = count(Reclammation::where('phoneclient', $request->phoneclient)->where('statut', 'closed')->get());
    //         $CounttreatmentReclam = count(Reclammation::where('phoneclient', $request->phoneclient)->where('statut', 'treatment')->get());
    //     }else{
    //         return 0;}
    //     return response()->json($CounttreatmentReclam, 200);
    // }

    //function pour chaque count de reclamation

    public function countReclamations(Request $request)
    {
        switch ($request->Countreclam) {
            case 'allstatus':
                //Réclamtion


                $CountNewReclam = count(Reclammation::where('phoneclient', $request->phoneclient)->where('statut', 'new')->get());
                $CountclosedReclam = count(Reclammation::where('phoneclient', $request->phoneclient)->where('statut', 'closed')->get());
                $CounttreatmentReclam = count(Reclammation::where('phoneclient', $request->phoneclient)->where('statut', 'treatment')->get());

                return response()->json([
                    'statut' => true,
                    'body' => [

                        'new' => $CountNewReclam,
                        'closed' => $CountclosedReclam,
                        'treatment' => $CounttreatmentReclam,

                    ],
                ]);
                break;

            case 'Counts':
                $StatusReclamation = Reclammation::where('phoneclient', $request->phoneclient)->get();
                return response()->json([
                    'statut' => true,
                    'body' => $StatusReclamation,
                ]);
        }
    }
    
    
    public function StorePreInscrisptionDubai() {

        $transPaiement = Historiquetrans::where('operation', 'preinscription')->whereIn('etat', ['atraiter', 'en_attente'])->get();
        if ($transPaiement) {
            foreach ($transPaiement as $paie) {

                //si le content est rempli
                if ($paie->content != "") {
                    $student = json_decode($paie->content,true);
                    //on reprend les data et on remplie la table DUBAI
                    $newpreInscription = new StudentsEsDubai([
                        'nom' => $student['nom'],
                        'prenom' => $student['prenom'],
                        'nationalite' => $student['nationalite'],
                        'statut' => $student['statut'],
                        'photo' => $student['photo'],
                        'email' => $student['email'],
                        'code_pays' => $student['codeIso'],
                        'montant' => $student['price'],
                        'numero' => $student['numero'],
                        'niveau' => $student['niveau'],
                        'cours' => $student['cours'],
                        'date_naissance' => $student['date'],
                        'periode' => $student['periode'],
                        'programme' => $student['programme'],
                        'customer'=>$student['customer'],
                    ]);
                    $newpreInscription->save();
                                        //ON CHANGE L'ETAT A CONFIRMEE
                    Historiquetrans::where('id', $paie->id)->update([
                        'etat' => 'CONFIRMEE'
                    ]);
                }
           
            }
             return 1;
        }
        return 0;
    }
    
    public function GetPreInscrisptionDubai(Request $request){

        $recupinfostudentES = StudentsEsDubai::where('numero', $request->numero)->get();

        if (count($recupinfostudentES)>0) {
            return response()->json([
                'studentES'=> $recupinfostudentES
            ]);
        }else{
            return 0;
        }

    }
}
