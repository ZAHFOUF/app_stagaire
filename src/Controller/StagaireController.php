<?php

namespace App\Controller;

use App\Repository\StagaireRepository ;
use App\Entity\Stagaire ;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;



use App\Form\StagaireType;





class StagaireController extends AbstractController
{

    public $repo ;


    public function __construct (StagaireRepository $r) {

        $this->repo = $r ;



    }


    // afficher touts les stagaires

    #[Route('/', name: 'app_stagaire')]
    public function index(EntityManagerInterface $entityManager): Response
    {

        $stagaires = $this->repo->findAll();

        // form delete 
        $stagaire = new Stagaire();
       $form_delete =  $this->createFormBuilder($stagaire)->setMethod('DELETE')->getForm();


        return $this->render('stagaire/index.html.twig', [
             'stagaires' => $stagaires ,
             'form_delete' => $form_delete
        ]);
    }





    // suprimmer un stagaire

    #[Route('/delete/{id}', name: 'app_stagaire_delete' , methods : ['DELETE','POST'])]
    public function destroy(EntityManagerInterface $entityManager,$id): Response
    {



        $stagaire = $this->repo->find($id);
        $entityManager->remove($stagaire);
        $entityManager->flush();


         return $this->redirectToRoute('app_stagaire');
    }




    // consulter un stagaire

    #[Route('/show/{id}', name: 'app_stagaire_show')]
    public function show(EntityManagerInterface $entityManager,$id) : Response {

        $stagaire = $this->repo->find($id);

        return $this->render('stagaire/show.html.twig', [
            'stagaire' => $stagaire ,
        ]);

    }


    // ajouter un stagaire 

    #[Route('/add', name: 'app_stagaire_add' , methods : ['GET','POST'])]
    public function create(EntityManagerInterface $entityManager,Request $request,fileUploader $fileUpload) : Response {

        $stagaire = new Stagaire() ;

        $form = $this->createForm(StagaireType::class, $stagaire);

        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            $new_stagaire = $form->getData();


            // upload the file
            $image = $fileUpload->upload($form->get('image')->getData());

            $new_stagaire->setImage($image);

            $entityManager->persist($new_stagaire);

            // save the entity in the data base

            $entityManager->flush();

           return $this->redirectToRoute('app_stagaire');


        }


        return $this->render('stagaire/form.html.twig',[
            'form' => $form->createView() ,
            'type' => 'ajouter'
        ]) ;

    }




   // editer un stagaire 

   #[Route('/edit/{id}', name: 'app_stagaire_edit' , methods : ['GET','POST'])]

   public function edit(EntityManagerInterface $entityManager,Request $request,fileUploader $fileUpload,$id) : Response {


       $stagaire_taget = $this->repo->find($id);
       $stagaire_empty = new Stagaire();


       $form = $this->createFormBuilder($stagaire_empty)
       ->add('nom', TextType::class,['attr' => array( 'value' => $stagaire_taget->getNom() )])
       ->add('prename', TextType::class ,['attr' => array( 'value' => $stagaire_taget->getPrename() )])
       ->add('age', IntegerType::class ,['attr' => array( 'value' => $stagaire_taget->getAge() )])
       ->add('image', FileType::class , ['required' => false])
       ->getForm();

       $form->handleRequest($request);

       if ( $form->isSubmitted() && $form->isValid() ) {

           $stagaire_taget->setNom ( $form->get('nom')->getData());
           $stagaire_taget->setPrename($form->get('prename')->getData())  ;
           $stagaire_taget->setAge($form->get('age')->getData()) ;



          // upload the file

           if ($form->get('image')->getData() !== null) {
            $image = $fileUpload->upload($form->get('image')->getData());

            $stagaire_taget->setImage($image);
           }

          
          $entityManager->persist($stagaire_taget);


          $entityManager->flush();


        

          return $this->redirectToRoute('app_stagaire');


       }

       return $this->render('stagaire/form.html.twig',[
           'form' => $form->createView() ,
           'type' => 'editer'
       ]) ;

   }



  


   // pdf stagaires
   #[Route('/download/stagaires', name: 'app_stagaire_download' , methods : ['GET','POST'])]
   public function generatePdf() : Response {

    $stagaires = $this->repo->findAll();

  

    $html = $this->render('pdfs/list.html.twig',[
        'stagaires' => $stagaires
    ]);



    // convertir HTML au PDF

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();


    // retourner le fichier binaire 

    return new Response (
        $dompdf->stream('resume', ["Attachment" => true]),
        Response::HTTP_OK,
        ['Content-Type' => 'application/pdf']
    );




    
    
   }


  



  
}
