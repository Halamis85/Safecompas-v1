<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Safecompas ',
            'descriptions' => 'Aplikace pro správu OOPP'
        ];
         return view('home', $data);
    }

    public function prehlOders()
    {
        $data = [
            'title' => 'Přehled objednávek',
            'descriptions' => 'Přehled objednávek'
        ];
        return view('prehlobj', $data);
    }

    public function menuOrd()
    {
        $data = [
            'title' => 'Nová objednávka',
            'descriptions' => 'Vytvoření nové objednávky OOPP'
        ];
        return view('menuorders', $data);
    }

    public function cardsEmploy()
    {
        $data = [
            'title' => 'Karta zaměstnance',
            'descriptions' => 'Přehled vydaných OOPP dle zaměstnance'
        ];
        return view('cards', $data);
    }
    public function employeeList()
    {
        $data = [
            'title' => 'Zpravá zaměstnanců',
            'descriptions' => 'Pro přidání nebo odebrání zaměstnance'
        ];
        return view('admin/employee_list',$data);
    }

    public function users()
    {
        $data = [
            'title' => 'Správa údajů pro přihlášení do aplikace',
            'descriptions' => 'Přístup do aplikace'
        ];
        return view('admin/users',$data);
    }

    public function admin()
    {
        $data = [
            'title' => 'Administrátorská sekce',
            'descriptions' => 'Rozdělovník administrace'
        ];
        return view('admin/indexAdmin', $data);
    }

    public function userAktivity()
    {
        $data = [
            'title' => 'Sledování aktivity uživatele',
            'descriptions' => 'Pro informace co uživatel změnil '
        ];
        return view('admin/users_aktivity', $data);
    }

    public function emailContact()
    {
        $data = [
        'title' => 'Správa e-mailových kontaktů<',
        'descriptions' => 'Email pro dodavatelů a ostatní učel'
    ];
        return view('admin.email_contact', $data);
    }

    public function adminEmployee()
    {
        $data = [
            'title' => 'Zpravá zaměstnanců',
            'descriptions' => 'Pro přidání nebo odebrání zaměstnance'
        ];

        return view('admin/employee_add', $data);
    }
    public function lekarnicke()
    {
        $data = [
            'title' => 'Lékárničky',
            'descriptions' => 'Správa a sledování lékárniček'
        ];
        return view('lekarnicky.index', $data);
    }




}
