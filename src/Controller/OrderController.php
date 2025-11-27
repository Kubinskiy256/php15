<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Repository\ClientRepository;
use App\Repository\DishRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\OrderFile;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/order')]
final class OrderController extends AbstractController
{

    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager,
        private ClientRepository $clientRepository,
        private DishRepository $dishRepository,
    ) {}


    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/list.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }


    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, SluggerInterface $slugger): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        $errors = $validator->validate($order);

        if ($form->isSubmitted() && $form->isValid() && count($errors) === 0) {

            $uploadedFiles = $form->get('uploadedFiles')->getData();

            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    $projectDir = $this->getParameter('kernel.project_dir');
                    try {
                        $uploadedFile->move($projectDir . "/public/uploads/orders", $newFilename);

                        $orderFile = new OrderFile();
                        $orderFile->setFilePath("/uploads/orders/" . $newFilename);
                        $orderFile->setOrder($order);

                        $order->addFile($orderFile);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Ошибка при загрузке файла: ' . $uploadedFile->getClientOriginalName());
                    }
                }
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Заказ успешно создан!');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }


    #[Route('/export-excel', name: 'app_order_export_excel', methods: ['GET'])]
    public function exportExcel(OrderRepository $orderRepository): StreamedResponse
    {
        $orders = $orderRepository->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'ID заказа');
        $sheet->setCellValue('B1', 'Клиент');
        $sheet->setCellValue('C1', 'Блюда');
        $sheet->setCellValue('D1', 'Кол-во блюд');
        $sheet->setCellValue('E1', 'Файлы');

        $row = 2;
        foreach ($orders as $order) {
            $dishes = [];
            foreach ($order->getDish() as $dish) {
                $dishes[] = $dish->getName();
            }

            $files = [];
            foreach ($order->getFiles() as $file) {
                $files[] = basename($file->getFilePath());
            }

            $sheet->setCellValue('A' . $row, $order->getId());
            $sheet->setCellValue('B' . $row, $order->getClient()->getName());
            $sheet->setCellValue('C' . $row, implode(', ', $dishes));
            $sheet->setCellValue('D' . $row, count($dishes));
            $sheet->setCellValue('E' . $row, implode(', ', $files));

            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="orders.xlsx"');

        return $response;
    }


    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);


        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Есть ошибки в форме. Проверьте все поля.');
        }


        if ($form->isSubmitted() && $form->isValid()) {

            $uploadedFiles = $form->get('uploadedFiles')->getData();

            if ($uploadedFiles) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    $projectDir = $this->getParameter('kernel.project_dir');
                    try {
                        $uploadedFile->move($projectDir . "/public/uploads/orders", $newFilename);

                        $orderFile = new OrderFile();
                        $orderFile->setFilePath("/uploads/orders/" . $newFilename);
                        $orderFile->setOrder($order);

                        $order->addFile($orderFile);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Ошибка при загрузке файла: ' . $uploadedFile->getClientOriginalName());
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Заказ успешно обновлен');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }


    #[Route('/{id}/delete-file/{fileId}', name: 'app_order_delete_file', methods: ['POST'])]
    public function deleteFile(Request $request, Order $order, int $fileId, EntityManagerInterface $entityManager): Response
    {

        $this->addFlash('debug', 'Начало удаления файла: ' . $fileId);

        if ($this->isCsrfTokenValid('delete-file-' . $fileId, $request->request->get('_token'))) {

            $file = $entityManager->getRepository(OrderFile::class)->find($fileId);

            if ($file && $file->getOrder() === $order) {

                $filePath = $this->getParameter('kernel.project_dir') . '/public' . $file->getFilePath();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $entityManager->remove($file);
                $entityManager->flush();

                $this->addFlash('success', 'Файл успешно удален');
            }
        }

        return $this->redirectToRoute('app_order_edit', ['id' => $order->getId()]);
    }


    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {

            foreach ($order->getFiles() as $file) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public' . $file->getFilePath();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'Заказ успешно удален');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/download/{fileId}', name: 'app_order_download_file', methods: ['GET'])]
    public function downloadFile(Order $order, int $fileId, EntityManagerInterface $entityManager): Response
    {
        $file = $entityManager->getRepository(OrderFile::class)->find($fileId);

        if (!$file || $file->getOrder() !== $order) {
            throw $this->createNotFoundException('Файл не найден');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public' . $file->getFilePath();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Файл не найден');
        }

        return $this->file($filePath);
    }


    
}